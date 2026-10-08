"""
main.py — نقطة دخول FastAPI
نظام المقابلات الذكي — بدون Whisper
"""
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from contextlib import asynccontextmanager
import time

from routers import health, interview, webrtc
from core.config import settings
from core.database import init_db


def preload_models():
    """تحميل الموديلات المطلوبة فقط (بدون Whisper)"""
    
    # ─── 1. MediaPipe FaceMesh ───
    print("━" * 60)
    print("⏳ [1/2] Loading MediaPipe FaceMesh...")
    t0 = time.time()
    try:
        from pipelines.face import get_face_mesh
        get_face_mesh()
        print(f"✅ MediaPipe loaded in {time.time() - t0:.1f}s")
    except Exception as e:
        print(f"⚠️ MediaPipe preload failed: {e}")
    
    # ─── 2. YOLO ───
    print("━" * 60)
    print("⏳ [2/2] Loading YOLO model...")
    t0 = time.time()
    try:
        from pipelines.yolo_pipe import get_yolo_model
        get_yolo_model()
        print(f"✅ YOLO loaded in {time.time() - t0:.1f}s")
    except Exception as e:
        print(f"⚠️ YOLO preload failed: {e}")
    
    print("━" * 60)
    print("🚀 All models ready!")


@asynccontextmanager
async def lifespan(app: FastAPI):
    await init_db()
    print(f"✅ Database connected — env: {settings.ENVIRONMENT}")
    preload_models()
    yield
    print("🔴 Interview AI Engine shutting down...")


app = FastAPI(
    title="Interview AI Engine",
    description="محرك تحليل المقابلات الذكي (بدون Whisper)",
    version="1.0.0",
    lifespan=lifespan,
)


def get_allowed_origins() -> list[str]:
    try:
        origins = settings.ALLOWED_ORIGINS
        if isinstance(origins, list):
            return origins
        if isinstance(origins, str):
            import json
            try:
                parsed = json.loads(origins)
                if isinstance(parsed, list):
                    return parsed
            except (json.JSONDecodeError, TypeError):
                pass
            return [o.strip() for o in origins.split(",") if o.strip()]
        return ["http://localhost:8000", "http://127.0.0.1:8000"]
    except Exception as e:
        print(f"⚠️ Could not parse ALLOWED_ORIGINS: {e}")
        return ["http://localhost:8000", "http://127.0.0.1:8000"]


allowed_origins = get_allowed_origins()
print(f"🌐 CORS allowed origins: {allowed_origins}")

app.add_middleware(
    CORSMiddleware,
    allow_origins=allowed_origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ─── Routers ───
app.include_router(health.router, tags=["Health"])
app.include_router(interview.router, prefix="/api/interview", tags=["Interview"])
app.include_router(webrtc.router, prefix="/api/webrtc", tags=["WebRTC"])