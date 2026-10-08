"""
pipelines/prosody.py — تحليل الصوت (Prosody)
باستخدام praat-parselmouth للدقة مع الصوت البشري
"""
import numpy as np
import parselmouth
from parselmouth.praat import call
from models.schemas import ProsodyIndicators


def analyze_prosody(audio_array: np.ndarray, sample_rate: int) -> ProsodyIndicators:
    """
    استخراج مؤشرات الصوت الخام من نافذة زمنية.
    المخرج: قيم خام بدون تسميات انفعالية.
    """
    if audio_array is None or len(audio_array) < sample_rate * 0.5:
        # أقل من 0.5 ثانية — لا يكفي للتحليل
        return ProsodyIndicators(confidence=0.0)

    try:
        # تحويل numpy array إلى Sound object
        if audio_array.dtype != np.float64:
            audio_array = audio_array.astype(np.float64)
        # Normalize
        max_val = np.max(np.abs(audio_array))
        if max_val > 0:
            audio_array = audio_array / max_val

        sound = parselmouth.Sound(audio_array, sampling_frequency=sample_rate)

        # ─── Pitch (F0) ───
        pitch = sound.to_pitch(time_step=0.01, pitch_floor=70, pitch_ceiling=400)
        pitch_values = pitch.selected_array["frequency"]
        voiced_frames = pitch_values[pitch_values > 0]

        pitch_mean = float(np.mean(voiced_frames)) if len(voiced_frames) > 0 else None
        pitch_variance = float(np.var(voiced_frames)) if len(voiced_frames) > 1 else None

        # ─── Intensity ───
        intensity = sound.to_intensity()
        intensity_values = intensity.values[0]
        intensity_mean = float(np.mean(intensity_values[intensity_values > 0])) if len(intensity_values) > 0 else None

        # ─── Pause Ratio ───
        # نسبة الصمت: الإطارات التي pitch_value = 0
        total_frames = len(pitch_values)
        silent_frames = np.sum(pitch_values == 0)
        pause_ratio = float(silent_frames / total_frames) if total_frames > 0 else None

        # ─── Speech Rate (تقريبي) ───
        # نستخدم انقطاعات الـ pitch كمؤشر للمقاطع
        duration_sec = sound.duration
        syllable_estimate = len(voiced_frames) * 0.01 / 0.15 if len(voiced_frames) > 0 else 0
        speech_rate = float(syllable_estimate / duration_sec) if duration_sec > 0 else None

        # ─── Confidence ───
        voiced_ratio = len(voiced_frames) / total_frames if total_frames > 0 else 0
        confidence = min(1.0, voiced_ratio * 1.5)  # أكثر كلامًا = ثقة أعلى

        return ProsodyIndicators(
            pitch_mean_hz=round(pitch_mean, 2) if pitch_mean else None,
            pitch_variance=round(pitch_variance, 4) if pitch_variance else None,
            speech_rate_syllables_per_sec=round(speech_rate, 2) if speech_rate else None,
            pause_ratio=round(pause_ratio, 3) if pause_ratio else None,
            intensity_mean_db=round(intensity_mean, 2) if intensity_mean else None,
            confidence=round(confidence, 3),
        )

    except Exception as e:
        print(f"⚠️ Prosody analysis error: {e}")
        return ProsodyIndicators(confidence=0.0)
