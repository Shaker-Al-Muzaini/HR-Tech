"""
pipelines/face.py — تحليل الوجه (MediaPipe FaceMesh)
مؤشرات AU خام بدون تسميات انفعالية
"""
import numpy as np
import cv2
import mediapipe as mp
from models.schemas import FaceIndicators

# ─── Mediapipe setup ───
mp_face_mesh = mp.solutions.face_mesh
_face_mesh = None


def get_face_mesh():
    global _face_mesh
    if _face_mesh is None:
        _face_mesh = mp_face_mesh.FaceMesh(
            static_image_mode=False,
            max_num_faces=1,
            refine_landmarks=True,
            min_detection_confidence=0.5,
            min_tracking_confidence=0.5,
        )
        print("✅ MediaPipe FaceMesh loaded")
    return _face_mesh


# ─── نقاط Landmark لحساب AU تقريبية ───
# (استنادًا إلى تعريف FACS بشكل مبسّط)
LANDMARKS = {
    "left_inner_brow": 107,
    "right_inner_brow": 336,
    "left_outer_brow": 70,
    "right_outer_brow": 300,
    "nose_tip": 4,
    "left_mouth_corner": 61,
    "right_mouth_corner": 291,
    "upper_lip_center": 13,
    "lower_lip_center": 14,
    "left_cheek": 116,
    "right_cheek": 345,
    "chin": 152,
}


def estimate_au_from_landmarks(landmarks, frame_h: int, frame_w: int) -> dict:
    """
    تقدير نشاط AU من نقاط Landmark.
    القيم من 0 إلى 1 (نشاط نسبي داخل النافذة).
    """
    def get_point(idx):
        lm = landmarks.landmark[idx]
        return np.array([lm.x * frame_w, lm.y * frame_h])

    try:
        left_brow = get_point(LANDMARKS["left_inner_brow"])
        right_brow = get_point(LANDMARKS["right_inner_brow"])
        nose = get_point(LANDMARKS["nose_tip"])
        left_corner = get_point(LANDMARKS["left_mouth_corner"])
        right_corner = get_point(LANDMARKS["right_mouth_corner"])
        upper_lip = get_point(LANDMARKS["upper_lip_center"])
        chin = get_point(LANDMARKS["chin"])
        left_cheek = get_point(LANDMARKS["left_cheek"])

        # حجم الوجه كمرجع للتطبيع
        face_height = np.linalg.norm(nose - chin)
        if face_height < 1:
            face_height = 1

        # AU1 — رفع الحاجب الداخلي (بُعد الحاجب عن الأنف)
        brow_nose_dist = np.linalg.norm((left_brow + right_brow) / 2 - nose)
        au1 = float(np.clip(brow_nose_dist / face_height, 0, 1))

        # AU4 — خفض الحاجب (قرب الحاجبين من بعض)
        brow_distance = np.linalg.norm(left_brow - right_brow)
        face_width = frame_w * 0.3  # تقدير عرض الوجه
        au4 = float(np.clip(1 - brow_distance / face_width, 0, 1))

        # AU12 — ابتسامة (سحب زوايا الشفة للأعلى)
        mouth_width = np.linalg.norm(left_corner - right_corner)
        lip_height = np.linalg.norm(upper_lip - chin)
        au12 = float(np.clip(mouth_width / (lip_height + 1), 0, 1))

        # AU6 — رفع الخد
        cheek_nose_dist = np.linalg.norm(left_cheek - nose)
        au6 = float(np.clip(1 - cheek_nose_dist / face_height, 0, 1))

        return {
            "au1": round(au1, 3),
            "au4": round(au4, 3),
            "au6": round(au6, 3),
            "au12": round(au12, 3),
        }
    except Exception:
        return {}


def analyze_face_frames(frames: list[np.ndarray]) -> tuple[FaceIndicators, int, int]:
    """
    تحليل قائمة إطارات الفيديو لنافذة زمنية.
    المخرج: (FaceIndicators, عدد الإطارات المكتشفة, العدد الكلي)
    """
    face_mesh = get_face_mesh()
    total_frames = len(frames)
    detected_frames = 0

    au1_list, au4_list, au6_list, au12_list = [], [], [], []
    head_positions = []

    for frame in frames:
        if frame is None:
            continue

        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        h, w = frame.shape[:2]
        results = face_mesh.process(rgb_frame)

        if results.multi_face_landmarks:
            detected_frames += 1
            landmarks = results.multi_face_landmarks[0]

            aus = estimate_au_from_landmarks(landmarks, h, w)
            if aus:
                au1_list.append(aus.get("au1", 0))
                au4_list.append(aus.get("au4", 0))
                au6_list.append(aus.get("au6", 0))
                au12_list.append(aus.get("au12", 0))

            # موقع الرأس (نقطة الأنف)
            nose_lm = landmarks.landmark[LANDMARKS["nose_tip"]]
            head_positions.append([nose_lm.x, nose_lm.y])

    # حساب تباين حركة الرأس
    head_var = None
    if len(head_positions) > 1:
        positions = np.array(head_positions)
        head_var = float(np.var(positions, axis=0).mean())

    confidence = detected_frames / total_frames if total_frames > 0 else 0.0

    def safe_mean(lst): return round(float(np.mean(lst)), 3) if lst else None

    return (
        FaceIndicators(
            au1_inner_brow_raise=safe_mean(au1_list),
            au4_brow_lowerer=safe_mean(au4_list),
            au6_cheek_raiser=safe_mean(au6_list),
            au12_lip_corner_puller=safe_mean(au12_list),
            head_movement_variance=round(head_var, 5) if head_var else None,
            confidence=round(confidence, 3),
        ),
        detected_frames,
        total_frames,
    )
