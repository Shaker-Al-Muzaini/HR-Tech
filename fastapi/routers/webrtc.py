"""
routers/webrtc.py — WebRTC SFU + AI + Waiting Room + Bi-directional + HR-first support
"""
import asyncio
import uuid
import json
import numpy as np
from datetime import datetime
from fractions import Fraction
from fastapi import APIRouter, WebSocket, WebSocketDisconnect, Depends, HTTPException
from aiortc import RTCPeerConnection, RTCSessionDescription, MediaStreamTrack, RTCConfiguration, RTCIceServer
from aiortc.contrib.media import MediaRelay
from av import AudioFrame, VideoFrame
from sqlalchemy import select, update, delete

from core.security import verify_api_key
from core.config import settings
from models.schemas import TimeWindowOutput
from pipelines.prosody import analyze_prosody
from pipelines.face import analyze_face_frames
from pipelines.yolo_pipe import analyze_objects_frames
from pipelines.quality_gate import evaluate_window_quality

router = APIRouter()
relay = MediaRelay()
active_sessions: dict = {}
hr_connections: dict[str, WebSocket] = {}
candidate_connections: dict[str, WebSocket] = {}


# ═══════════════════════════════════════════════════════════
# Placeholder Tracks — صامتة وسوداء
# ═══════════════════════════════════════════════════════════
class SilentAudioTrack(MediaStreamTrack):
    kind = "audio"
    def __init__(self):
        super().__init__()
        self._timestamp = 0
    async def recv(self):
        await asyncio.sleep(0.02)
        frame = AudioFrame(format="s16", layout="mono", samples=960)
        frame.pts = self._timestamp
        frame.sample_rate = 48000
        frame.time_base = Fraction(1, 48000)
        self._timestamp += 960
        for p in frame.planes:
            p.update(bytes(p.buffer_size))
        return frame


class BlackVideoTrack(MediaStreamTrack):
    kind = "video"
    def __init__(self):
        super().__init__()
        self._timestamp = 0
    async def recv(self):
        await asyncio.sleep(1/30)
        frame = VideoFrame(width=320, height=240)
        frame.pts = self._timestamp
        frame.time_base = Fraction(1, 30)
        self._timestamp += 1
        for p in frame.planes:
            p.update(bytes(p.buffer_size))
        return frame


def _make_pc() -> RTCPeerConnection:
    config = RTCConfiguration(iceServers=[
        RTCIceServer(urls="stun:stun.l.google.com:19302"),
        RTCIceServer(urls="stun:stun1.l.google.com:19302"),
    ])
    return RTCPeerConnection(configuration=config)


class InterviewSession:
    MAX_AUDIO_CHUNKS = 250
    MAX_VIDEO_FRAMES = 90
    HR_GRACE_PERIOD = 30

    def __init__(self, session_id: str, interview_id: str, language: str = "ar"):
        self.session_id = session_id
        self.interview_id = interview_id
        self.language = language

        self.candidate_pc = _make_pc()
        self.hr_pc = _make_pc()

        self.candidate_connected = False
        self.hr_connected = False

        self.candidate_raw_tracks: dict = {}
        self.candidate_relay_subs: dict = {}
        self.hr_raw_tracks: dict = {}
        self.hr_relay_subs: dict = {}

        self.candidate_senders: dict = {}
        self.hr_senders: dict = {}

        self.hr_placeholder = {"audio": False, "video": False}
        self.candidate_placeholder = {"audio": False, "video": False}

        self.db_session_id = None
        self.audio_buffer: list = []
        self.video_frames: list = []
        self.sample_rate = 16000
        self.window_index = 0
        self._window_task = None

        self.started_at = datetime.utcnow()
        self.ended = False
        self._cleanup_task = None
        self._hr_grace_task = None

    async def start_window_processor(self):
        if self._window_task is None:
            self._window_task = asyncio.create_task(self._process_windows())

    async def _process_windows(self):
        while not self.ended:
            await asyncio.sleep(settings.QUALITY_WINDOW_SECONDS)
            if self.audio_buffer or self.video_frames:
                await self._analyze_current_window()

    async def _analyze_current_window(self):
        audio_data = np.concatenate(self.audio_buffer) if self.audio_buffer else None
        video_frames = self.video_frames.copy()
        self.audio_buffer = []
        self.video_frames = []
        window_start = self.window_index * settings.QUALITY_WINDOW_SECONDS
        window_end = window_start + settings.QUALITY_WINDOW_SECONDS
        print(f"🔍 Window {self.window_index}")
        try:
            prosody_task = asyncio.to_thread(analyze_prosody, audio_data, self.sample_rate) if audio_data is not None else asyncio.sleep(0, result=None)
            face_task = asyncio.to_thread(analyze_face_frames, video_frames) if video_frames else asyncio.sleep(0, result=None)
            yolo_task = asyncio.to_thread(analyze_objects_frames, video_frames) if video_frames else asyncio.sleep(0, result=None)
            prosody_result, face_result, yolo_result = await asyncio.gather(prosody_task, face_task, yolo_task)
            face_indicators = None
            detected_frames = 0
            total_frames = len(video_frames)
            if face_result and isinstance(face_result, tuple):
                face_indicators, detected_frames, total_frames = face_result
            quality = evaluate_window_quality(audio_data, self.sample_rate, detected_frames, total_frames)
            from models.schemas import (ProsodyIndicators, WhisperIndicators, FaceIndicators, ObjectIndicators)
            output = TimeWindowOutput(
                session_id=self.session_id,
                window_index=self.window_index,
                start_time_sec=window_start,
                end_time_sec=window_end,
                prosody=prosody_result or ProsodyIndicators(),
                transcript=WhisperIndicators(),
                face=face_indicators or FaceIndicators(),
                objects=yolo_result or ObjectIndicators(),
                quality=quality,
            )
            self.window_index += 1
            await self._send_to_hr(output)
            await self._save_window_to_db(output)
        except Exception as e:
            print(f"⚠️ Analysis error: {e}")

    async def _send_to_hr(self, output: TimeWindowOutput):
        ws = hr_connections.get(self.session_id)
        if ws:
            try:
                await ws.send_json(output.model_dump(mode="json"))
            except Exception:
                pass

    async def _send_event_to_hr(self, event_type: str, data: dict = None):
        ws = hr_connections.get(self.session_id)
        if ws:
            try:
                await ws.send_json({
                    "event": event_type, "session_id": self.session_id,
                    "timestamp": datetime.utcnow().isoformat(), "data": data or {},
                })
            except Exception:
                pass

    async def _send_event_to_candidate(self, event_type: str, data: dict = None):
        ws = candidate_connections.get(self.session_id)
        if ws:
            try:
                await ws.send_json({
                    "event": event_type, "session_id": self.session_id,
                    "timestamp": datetime.utcnow().isoformat(), "data": data or {},
                })
                return True
            except Exception:
                return False
        return False

    async def _ensure_db_session(self, db) -> int:
        if self.db_session_id is not None:
            return self.db_session_id
        from models.db_models import InterviewSessionModel
        result = await db.execute(select(InterviewSessionModel).where(InterviewSessionModel.candidate_token == self.session_id))
        row = result.scalar_one_or_none()
        if not row:
            return None
        self.db_session_id = row.id
        return self.db_session_id

    async def _clear_old_windows(self):
        from core.database import AsyncSessionLocal
        from models.db_models import TimeWindowModel
        try:
            async with AsyncSessionLocal() as db:
                session_id = await self._ensure_db_session(db)
                if not session_id:
                    return
                await db.execute(delete(TimeWindowModel).where(TimeWindowModel.interview_session_id == session_id))
                await db.commit()
        except Exception as e:
            print(f"❌ Clear failed: {e}")

    async def _save_window_to_db(self, output: TimeWindowOutput):
        from core.database import AsyncSessionLocal
        from models.db_models import TimeWindowModel
        try:
            async with AsyncSessionLocal() as db:
                session_id = await self._ensure_db_session(db)
                if not session_id:
                    return
                tw = TimeWindowModel(
                    interview_session_id=session_id,
                    window_index=output.window_index, start_time_sec=output.start_time_sec, end_time_sec=output.end_time_sec,
                    pitch_mean_hz=output.prosody.pitch_mean_hz, pitch_variance=output.prosody.pitch_variance,
                    speech_rate=output.prosody.speech_rate_syllables_per_sec, pause_ratio=output.prosody.pause_ratio,
                    intensity_mean_db=output.prosody.intensity_mean_db, prosody_confidence=output.prosody.confidence,
                    transcript=None, language_detected=None, whisper_confidence=0.0,
                    au1_inner_brow_raise=output.face.au1_inner_brow_raise, au4_brow_lowerer=output.face.au4_brow_lowerer,
                    au6_cheek_raiser=output.face.au6_cheek_raiser, au12_lip_corner_puller=output.face.au12_lip_corner_puller,
                    head_movement_variance=output.face.head_movement_variance, face_confidence=output.face.confidence,
                    objects_detected=output.objects.objects_detected or [], phone_detected=output.objects.phone_detected,
                    person_count=output.objects.person_count, audio_snr_db=output.quality.audio_snr_db,
                    audio_quality=output.quality.audio_quality, face_detection_rate=output.quality.face_detection_rate,
                    face_quality=output.quality.face_quality, overall_quality=output.quality.overall_quality,
                    human_review_required=output.quality.human_review_required,
                )
                db.add(tw)
                await db.commit()
        except Exception as e:
            print(f"❌ DB save: {e}")

    async def _finalize_db(self):
        from core.database import AsyncSessionLocal
        from models.db_models import InterviewSessionModel
        try:
            async with AsyncSessionLocal() as db:
                session_id = await self._ensure_db_session(db)
                if not session_id:
                    return
                await db.execute(update(InterviewSessionModel).where(InterviewSessionModel.id == session_id).values(
                    status='completed', ended_at=datetime.utcnow(), window_count=self.window_index,
                ))
                await db.commit()
        except Exception as e:
            print(f"❌ DB finalize: {e}")

    async def _hr_grace_timer(self):
        try:
            await asyncio.sleep(self.HR_GRACE_PERIOD)
            if not self.hr_connected and not self.ended:
                await self._send_event_to_candidate("hr_left", {"message": "المشرف غادر"})
        except asyncio.CancelledError:
            pass

    async def on_hr_disconnected(self):
        if self._hr_grace_task and not self._hr_grace_task.done():
            self._hr_grace_task.cancel()
        self._hr_grace_task = asyncio.create_task(self._hr_grace_timer())

    async def on_hr_reconnected(self):
        if self._hr_grace_task and not self._hr_grace_task.done():
            self._hr_grace_task.cancel()
        self.hr_connected = True

    async def on_candidate_left(self):
        if self.ended:
            return
        await self._send_event_to_hr("candidate_left", {"message": "المرشح غادر"})
        self._cleanup_task = asyncio.create_task(self._delayed_cleanup())

    async def _delayed_cleanup(self):
        try:
            await asyncio.sleep(60)
            if self.ended or self.candidate_connected:
                return
            await self.cleanup()
        except asyncio.CancelledError:
            pass

    async def end_interview(self, by: str = "hr"):
        await self._send_event_to_hr("interview_ended", {"by": by})
        await self._send_event_to_candidate("interview_ended", {"by": by, "message": "تم إنهاء المقابلة"})
        await asyncio.sleep(0.5)
        await self.cleanup()

    async def cleanup(self):
        if self.ended:
            return
        self.ended = True
        if self._window_task: self._window_task.cancel()
        if self._hr_grace_task: self._hr_grace_task.cancel()
        if self._cleanup_task: self._cleanup_task.cancel()
        await self._finalize_db()
        try: await self.candidate_pc.close()
        except: pass
        try: await self.hr_pc.close()
        except: pass
        active_sessions.pop(self.session_id, None)


@router.get("/status/{session_id}")
async def get_status(session_id: str):
    session = active_sessions.get(session_id)
    return {
        "session_id": session_id,
        "candidate_waiting": session_id in candidate_connections,
        "candidate_connected": session.candidate_connected if session else False,
        "hr_connected": session_id in hr_connections,
        "session_active": session_id in active_sessions,
    }


@router.post("/approve/{session_id}", dependencies=[Depends(verify_api_key)])
async def approve_candidate(session_id: str):
    print(f"✅ HR approved: {session_id}")
    ws = candidate_connections.get(session_id)
    if not ws:
        raise HTTPException(404, "Candidate not connected")
    try:
        await ws.send_json({"event": "hr_approved", "session_id": session_id, "data": {"message": "تم قبولك"}})
    except Exception as e:
        raise HTTPException(500, str(e))
    return {"status": "approved", "session_id": session_id}


@router.websocket("/ws/{session_id}")
async def ws_endpoint(websocket: WebSocket, session_id: str, role: str = "hr"):
    await websocket.accept()

    if role == "candidate":
        candidate_connections[session_id] = websocket
        print(f"📡 Candidate WS: {session_id[:20]}...")
        hr_ws = hr_connections.get(session_id)
        if hr_ws:
            try:
                await hr_ws.send_json({"event": "candidate_waiting", "session_id": session_id, "data": {"message": "المرشح ينتظر"}})
            except Exception:
                pass
    else:
        hr_connections[session_id] = websocket
        print(f"📡 HR WS: {session_id[:20]}...")
        if session_id in candidate_connections:
            try:
                await websocket.send_json({"event": "candidate_waiting", "session_id": session_id, "data": {"message": "المرشح ينتظر"}})
            except Exception:
                pass

    try:
        while True:
            data = await websocket.receive_text()
            try:
                msg = json.loads(data)
                action = msg.get("action")
                session = active_sessions.get(session_id)
                if action == "end_interview" and session:
                    await session.end_interview(by="hr")
                    break
                elif action == "stop" and session:
                    await session.cleanup()
                    break
            except json.JSONDecodeError:
                pass
    except WebSocketDisconnect:
        print(f"📡 {role} disconnected")
    finally:
        if role == "candidate":
            candidate_connections.pop(session_id, None)
        else:
            hr_connections.pop(session_id, None)


@router.post("/offer", dependencies=[Depends(verify_api_key)])
async def webrtc_offer(body: dict):
    session_id = body.get("session_id") or str(uuid.uuid4())
    role = body.get("role", "candidate")
    sdp = body.get("sdp")
    sdp_type = body.get("type", "offer")

    if not sdp:
        raise HTTPException(400, "SDP required")
    if role not in ("candidate", "hr"):
        raise HTTPException(400, "Invalid role")

    print(f"\n{'='*60}\n📥 Offer | {session_id[:20]}... | {role}\n{'='*60}")

    if session_id in active_sessions:
        existing = active_sessions[session_id]
        if existing.candidate_pc.connectionState in ("closed", "failed") and role == "candidate":
            await existing.cleanup()

    # ═══════════════════════════════════════════════════════════
    # ✅ اسمح لكلا الطرفين بإنشاء الجلسة
    # ═══════════════════════════════════════════════════════════
    if session_id not in active_sessions:
        session = InterviewSession(session_id, body.get("interview_id", ""), body.get("language", "ar"))
        active_sessions[session_id] = session
        print(f"🆕 Session created by {role}")

        if role == "candidate":
            await session.start_window_processor()
            await session._clear_old_windows()
            print(f"   ✓ Window processor started")
        else:
            print(f"   ⏳ Window processor postponed (waiting for candidate)")
    else:
        session = active_sessions[session_id]

        if role == "candidate" and session._window_task is None:
            await session.start_window_processor()
            await session._clear_old_windows()
            print(f"↪️  Candidate joined HR-created session, processor started")

    pc = session.candidate_pc if role == "candidate" else session.hr_pc

    @pc.on("track")
    def on_track(track: MediaStreamTrack):
        kind = track.kind
        print(f"🎯 [{role}] INCOMING track: {kind}")
        sub = relay.subscribe(track)

        if role == "candidate":
            session.candidate_raw_tracks[kind] = track
            session.candidate_relay_subs[kind] = sub
            if kind == "audio":
                asyncio.create_task(_handle_audio_track(track, session))
            elif kind == "video":
                asyncio.create_task(_handle_video_track(track, session))
            hr_sender = session.hr_senders.get(kind)
            if hr_sender is not None:
                try:
                    hr_sender.replaceTrack(sub)
                    session.hr_placeholder[kind] = False
                    print(f"↪️  [LIVE] candidate {kind} → HR")
                except Exception as e:
                    print(f"❌ [LIVE] candidate {kind} → HR: {e}")
        else:
            session.hr_raw_tracks[kind] = track
            session.hr_relay_subs[kind] = sub
            cand_sender = session.candidate_senders.get(kind)
            if cand_sender is not None:
                try:
                    cand_sender.replaceTrack(sub)
                    session.candidate_placeholder[kind] = False
                    print(f"↪️  [LIVE] HR {kind} → candidate")
                except Exception as e:
                    print(f"❌ [LIVE] HR {kind} → candidate: {e}")

    @pc.on("connectionstatechange")
    async def on_state_change():
        state = pc.connectionState
        print(f"🔗 [{role}] {state}")
        if state == "connected":
            if role == "candidate":
                session.candidate_connected = True
            else:
                await session.on_hr_reconnected()
        elif state in ("failed", "closed"):
            if role == "candidate":
                if session.candidate_connected:
                    session.candidate_connected = False
                    await session.on_candidate_left()
            else:
                session.hr_connected = False
                await session.on_hr_disconnected()

    offer = RTCSessionDescription(sdp=sdp, type=sdp_type)
    await pc.setRemoteDescription(offer)

    transceivers = pc.getTransceivers()
    print(f"\n📡 Analyzing {role} transceivers:")
    for i, t in enumerate(transceivers):
        print(f"   [{i}] kind={t.kind} direction={t.direction} mid={t.mid}")

    if role == "candidate":
        for i, t in enumerate(transceivers):
            if i == 2 and t.kind == "audio":
                session.candidate_senders["audio"] = t.sender
                t.direction = "sendonly"
                if session.hr_relay_subs.get("audio"):
                    t.sender.replaceTrack(session.hr_relay_subs["audio"])
                    print(f"   ✓ candidate_senders[audio] = HR sub")
                else:
                    t.sender.replaceTrack(SilentAudioTrack())
                    session.candidate_placeholder["audio"] = True
                    print(f"   ✓ candidate_senders[audio] = SILENT placeholder")
            elif i == 3 and t.kind == "video":
                session.candidate_senders["video"] = t.sender
                t.direction = "sendonly"
                if session.hr_relay_subs.get("video"):
                    t.sender.replaceTrack(session.hr_relay_subs["video"])
                    print(f"   ✓ candidate_senders[video] = HR sub")
                else:
                    t.sender.replaceTrack(BlackVideoTrack())
                    session.candidate_placeholder["video"] = True
                    print(f"   ✓ candidate_senders[video] = BLACK placeholder")
    else:
        for i, t in enumerate(transceivers):
            if i == 2 and t.kind == "audio":
                session.hr_senders["audio"] = t.sender
                t.direction = "sendonly"
                if session.candidate_relay_subs.get("audio"):
                    t.sender.replaceTrack(session.candidate_relay_subs["audio"])
                    print(f"   ✓ hr_senders[audio] = candidate sub")
                else:
                    t.sender.replaceTrack(SilentAudioTrack())
                    session.hr_placeholder["audio"] = True
                    print(f"   ✓ hr_senders[audio] = SILENT placeholder")
            elif i == 3 and t.kind == "video":
                session.hr_senders["video"] = t.sender
                t.direction = "sendonly"
                if session.candidate_relay_subs.get("video"):
                    t.sender.replaceTrack(session.candidate_relay_subs["video"])
                    print(f"   ✓ hr_senders[video] = candidate sub")
                else:
                    t.sender.replaceTrack(BlackVideoTrack())
                    session.hr_placeholder["video"] = True
                    print(f"   ✓ hr_senders[video] = BLACK placeholder")

    answer = await pc.createAnswer()
    await pc.setLocalDescription(answer)

    return {
        "session_id": session_id,
        "role": role,
        "sdp": pc.localDescription.sdp,
        "type": pc.localDescription.type,
    }


@router.delete("/session/{session_id}", dependencies=[Depends(verify_api_key)])
async def close_session(session_id: str):
    session = active_sessions.get(session_id)
    if session:
        await session.cleanup()
    return {"status": "closed", "session_id": session_id}


async def _handle_audio_track(track: MediaStreamTrack, session: InterviewSession):
    while not session.ended:
        try:
            frame = await track.recv()
            audio_array = frame.to_ndarray().flatten().astype(np.float32)
            if np.max(np.abs(audio_array)) > 0:
                audio_array = audio_array / 32768.0
            session.audio_buffer.append(audio_array)
            session.sample_rate = frame.sample_rate
            if len(session.audio_buffer) > InterviewSession.MAX_AUDIO_CHUNKS:
                session.audio_buffer = session.audio_buffer[-InterviewSession.MAX_AUDIO_CHUNKS:]
        except Exception:
            break


async def _handle_video_track(track: MediaStreamTrack, session: InterviewSession):
    import cv2
    while not session.ended:
        try:
            frame = await track.recv()
            img = frame.to_ndarray(format="bgr24")
            if img.shape[0] > 480:
                scale = 480 / img.shape[0]
                img = cv2.resize(img, (int(img.shape[1] * scale), 480))
            session.video_frames.append(img)
            if len(session.video_frames) > InterviewSession.MAX_VIDEO_FRAMES:
                session.video_frames = session.video_frames[-InterviewSession.MAX_VIDEO_FRAMES:]
        except Exception:
            break