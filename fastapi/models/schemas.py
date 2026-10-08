"""
models/schemas.py — نماذج البيانات (Pydantic)
المخرج الخام لكل نافذة زمنية
"""
from pydantic import BaseModel, Field
from typing import Optional, Literal
from datetime import datetime


# ─────────────────────────────────────────────────────────
# Signal Quality
# ─────────────────────────────────────────────────────────

class SignalQuality(BaseModel):
    """وسم جودة الإشارة لكل نافذة زمنية"""
    audio_snr_db: Optional[float] = Field(None, description="نسبة الإشارة إلى الضوضاء بالديسيبل")
    audio_quality: Literal["excellent", "acceptable", "low"] = "acceptable"
    face_detection_rate: Optional[float] = Field(None, description="نسبة الإطارات التي تم فيها كشف الوجه")
    face_quality: Literal["excellent", "acceptable", "low"] = "acceptable"
    overall_quality: Literal["excellent", "acceptable", "low"] = "acceptable"
    human_review_required: bool = False


# ─────────────────────────────────────────────────────────
# Raw Indicators — المؤشرات الخام
# ─────────────────────────────────────────────────────────

class ProsodyIndicators(BaseModel):
    """مؤشرات الصوت الخام"""
    pitch_mean_hz: Optional[float] = None
    pitch_variance: Optional[float] = None
    speech_rate_syllables_per_sec: Optional[float] = None
    pause_ratio: Optional[float] = None           # نسبة الصمت إلى الكلام
    intensity_mean_db: Optional[float] = None
    confidence: float = Field(0.0, ge=0.0, le=1.0)


class WhisperIndicators(BaseModel):
    """مخرجات النسخ"""
    transcript: str = ""
    language_detected: str = "ar"
    avg_token_confidence: Optional[float] = None
    word_error_rate_estimate: Optional[float] = None
    confidence: float = Field(0.0, ge=0.0, le=1.0)


class FaceIndicators(BaseModel):
    """مؤشرات الوجه الخام (AU Activity)"""
    au1_inner_brow_raise: Optional[float] = None   # رفع الحاجب الداخلي
    au2_outer_brow_raise: Optional[float] = None   # رفع الحاجب الخارجي
    au4_brow_lowerer: Optional[float] = None       # خفض الحاجب
    au6_cheek_raiser: Optional[float] = None       # رفع الخد
    au12_lip_corner_puller: Optional[float] = None # سحب زاوية الشفة (ابتسامة)
    au15_lip_corner_depressor: Optional[float] = None
    au17_chin_raiser: Optional[float] = None
    head_movement_variance: Optional[float] = None
    confidence: float = Field(0.0, ge=0.0, le=1.0)


class ObjectIndicators(BaseModel):
    """الكائنات المكتشفة"""
    objects_detected: list[str] = []
    phone_detected: bool = False
    person_count: int = 1
    confidence: float = Field(0.0, ge=0.0, le=1.0)


# ─────────────────────────────────────────────────────────
# Time Window Output — مخرج النافذة الزمنية الكاملة
# ─────────────────────────────────────────────────────────

class TimeWindowOutput(BaseModel):
    """
    المخرج الخام الكامل لكل نافذة زمنية (5 ثوانٍ افتراضيًا).
    لا توجد تسميات جاهزة (قلق/توتر) — القرار للمراجع البشري.
    """
    session_id: str
    window_index: int
    start_time_sec: float
    end_time_sec: float
    timestamp: datetime = Field(default_factory=datetime.utcnow)

    # المؤشرات الخام
    prosody: ProsodyIndicators = Field(default_factory=ProsodyIndicators)
    transcript: WhisperIndicators = Field(default_factory=WhisperIndicators)
    face: FaceIndicators = Field(default_factory=FaceIndicators)
    objects: ObjectIndicators = Field(default_factory=ObjectIndicators)

    # جودة الإشارة
    quality: SignalQuality = Field(default_factory=SignalQuality)


# ─────────────────────────────────────────────────────────
# API Schemas
# ─────────────────────────────────────────────────────────

class SessionStartRequest(BaseModel):
    interview_id: str
    candidate_id: str
    language: Literal["ar", "en", "ar-en"] = "ar"


class SessionStartResponse(BaseModel):
    session_id: str
    webrtc_offer_url: str
    status: str = "ready"


class HealthResponse(BaseModel):
    status: str
    environment: str
    models_loaded: dict
