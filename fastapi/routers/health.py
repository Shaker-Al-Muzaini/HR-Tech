"""
routers/health.py — نقطة فحص صحة الخدمة
"""
from fastapi import APIRouter
from models.schemas import HealthResponse
from core.config import settings

router = APIRouter()


@router.get("/health", response_model=HealthResponse)
async def health_check():
    """
    يُستخدم من Docker healthcheck ومن Laravel للتحقق من جاهزية FastAPI
    """
    return HealthResponse(
        status="ok",
        environment=settings.ENVIRONMENT,
        models_loaded={
            "whisper": settings.WHISPER_MODEL,
            "yolo": settings.YOLO_MODEL,
            "mediapipe": "facemesh",
            "praat": "parselmouth",
        },
    )
