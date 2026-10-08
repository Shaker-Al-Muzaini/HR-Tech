"""
models/db_models.py — SQLAlchemy models للاتصال بـ PostgreSQL
تطابق جداول Laravel migrations — للقراءة/الكتابة فقط (لا إنشاء جداول)
"""
from sqlalchemy import Column, BigInteger, Integer, String, Float, Boolean, DateTime, ForeignKey, Text
from sqlalchemy.dialects.postgresql import JSONB
from datetime import datetime

from core.database import Base


class InterviewSessionModel(Base):
    """جدول interview_sessions — نستخدمه للربط بـ candidate_token"""
    __tablename__ = "interview_sessions"

    id = Column(BigInteger, primary_key=True)
    interview_id = Column(BigInteger)
    session_uuid = Column(String(36))
    candidate_token = Column(String(48), unique=True)
    fastapi_session_id = Column(String(64), nullable=True)
    status = Column(String(20))
    window_count = Column(Integer, default=0)
    candidate_joined_at = Column(DateTime, nullable=True)
    calibration_completed_at = Column(DateTime, nullable=True)
    ended_at = Column(DateTime, nullable=True)
    created_at = Column(DateTime)
    updated_at = Column(DateTime)


class TimeWindowModel(Base):
    """جدول time_windows — نحفظ فيه كل نافذة زمنية"""
    __tablename__ = "time_windows"

    id = Column(BigInteger, primary_key=True)
    interview_session_id = Column(
        BigInteger,
        ForeignKey("interview_sessions.id"),
        index=True,
    )
    window_index = Column(Integer)
    start_time_sec = Column(Float)
    end_time_sec = Column(Float)

    # ─── Prosody ───
    pitch_mean_hz = Column(Float, nullable=True)
    pitch_variance = Column(Float, nullable=True)
    speech_rate = Column(Float, nullable=True)
    pause_ratio = Column(Float, nullable=True)
    intensity_mean_db = Column(Float, nullable=True)
    prosody_confidence = Column(Float, default=0.0)

    # ─── Whisper (لن نستخدمها — تبقى فارغة) ───
    transcript = Column(Text, nullable=True)
    language_detected = Column(String(5), nullable=True)
    whisper_confidence = Column(Float, default=0.0)

    # ─── Face (FACS) ───
    au1_inner_brow_raise = Column(Float, nullable=True)
    au4_brow_lowerer = Column(Float, nullable=True)
    au6_cheek_raiser = Column(Float, nullable=True)
    au12_lip_corner_puller = Column(Float, nullable=True)
    head_movement_variance = Column(Float, nullable=True)
    face_confidence = Column(Float, default=0.0)

    # ─── Objects ───
    objects_detected = Column(JSONB, nullable=True)
    phone_detected = Column(Boolean, default=False)
    person_count = Column(Integer, default=1)

    # ─── Quality ───
    audio_snr_db = Column(Float, nullable=True)
    audio_quality = Column(String(20), nullable=True)
    face_detection_rate = Column(Float, nullable=True)
    face_quality = Column(String(20), nullable=True)
    overall_quality = Column(String(20), nullable=True)
    human_review_required = Column(Boolean, default=False)

    recorded_at = Column(DateTime, default=datetime.utcnow)