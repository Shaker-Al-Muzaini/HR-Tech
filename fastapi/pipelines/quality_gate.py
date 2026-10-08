"""
pipelines/quality_gate.py — Signal Quality Gate
يراقب جودة كل قناة ويُوسم النافذة الزمنية
"""
import numpy as np
from models.schemas import SignalQuality
from core.config import settings


def compute_snr(audio_array: np.ndarray, sample_rate: int) -> float:
    """
    حساب SNR بسيط: نسبة الطاقة الكلية إلى ضوضاء خلفية مقدَّرة
    """
    if audio_array is None or len(audio_array) == 0:
        return 0.0
    signal_power = np.mean(audio_array**2)
    # تقدير الضوضاء من أهدأ 10% من الإشارة
    sorted_frames = np.sort(np.abs(audio_array))
    noise_level = np.mean(sorted_frames[:max(1, len(sorted_frames) // 10)]**2)
    if noise_level == 0:
        return 60.0  # إشارة نظيفة جدًا
    snr = 10 * np.log10(signal_power / noise_level)
    return float(snr)


def classify_snr(snr_db: float) -> str:
    """تصنيف جودة الصوت بناءً على SNR"""
    if snr_db >= 20:
        return "excellent"
    elif snr_db >= settings.MIN_SNR_DB:
        return "acceptable"
    else:
        return "low"


def classify_face_quality(detection_rate: float) -> str:
    """تصنيف جودة الكشف عن الوجه"""
    if detection_rate >= 0.90:
        return "excellent"
    elif detection_rate >= (1 - settings.MAX_FAIL_RATE):
        return "acceptable"
    else:
        return "low"


def evaluate_window_quality(
    audio_array: np.ndarray,
    sample_rate: int,
    face_detected_frames: int,
    total_frames: int,
) -> SignalQuality:
    """
    تقييم جودة النافذة الزمنية كاملة.
    المخرج: SignalQuality مع وسم overall_quality و human_review_required.
    """
    # ─── جودة الصوت ───
    snr_db = compute_snr(audio_array, sample_rate) if audio_array is not None else 0.0
    audio_quality = classify_snr(snr_db)

    # ─── جودة الوجه ───
    detection_rate = (face_detected_frames / total_frames) if total_frames > 0 else 0.0
    face_quality = classify_face_quality(detection_rate)

    # ─── الجودة الكلية ───
    quality_scores = {"excellent": 2, "acceptable": 1, "low": 0}
    avg_score = (quality_scores[audio_quality] + quality_scores[face_quality]) / 2

    if avg_score >= 1.5:
        overall = "excellent"
    elif avg_score >= 0.5:
        overall = "acceptable"
    else:
        overall = "low"

    human_review = overall == "low"

    return SignalQuality(
        audio_snr_db=round(snr_db, 2),
        audio_quality=audio_quality,
        face_detection_rate=round(detection_rate, 3),
        face_quality=face_quality,
        overall_quality=overall,
        human_review_required=human_review,
    )
