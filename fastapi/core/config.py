"""
core/config.py — إعدادات التطبيق من متغيرات البيئة
"""
from pydantic_settings import BaseSettings
from typing import List


class Settings(BaseSettings):
    # ─── عام ───
    ENVIRONMENT: str = "development"
    API_KEY: str = "dev-api-key-change-in-production"

    # ─── قاعدة البيانات ───
    DB_URL: str = "postgresql+asyncpg://interview_user:interview_pass@localhost:5432/interview_db"

    # ─── Redis ───
    REDIS_URL: str = "redis://:redis_pass@localhost:6379/0"

    # ─── ملفات التسجيل ───
    RECORDINGS_PATH: str = "./recordings"   # مسار محلي (بيئة Laragon)

    # ─── نماذج الذكاء الاصطناعي ───
    WHISPER_MODEL: str = "base"          # base أو small
    YOLO_MODEL: str = "yolov8n.pt"       # nano — الأخف

    # ─── Signal Quality Gate ───
    QUALITY_WINDOW_SECONDS: int = 5      # حجم النافذة الزمنية بالثواني
    MAX_FAIL_RATE: float = 0.15          # 15% فشل = استبعاد القناة
    MIN_SNR_DB: float = 10.0             # حد أدنى لـ SNR بالديسيبل

    # ─── CORS ───
    ALLOWED_ORIGINS: List[str] = [
        "http://localhost:8000",
        "http://127.0.0.1:8000",
    ]

    class Config:
        env_file = ".env"
        case_sensitive = True


settings = Settings()
