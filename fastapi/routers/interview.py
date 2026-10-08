"""
routers/interview.py — إدارة المقابلات (CRUD + نتائج)
"""
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.ext.asyncio import AsyncSession
from core.database import get_db
from core.security import verify_api_key
from models.schemas import SessionStartRequest, SessionStartResponse
import uuid

router = APIRouter()


@router.post("/session/start", response_model=SessionStartResponse, dependencies=[Depends(verify_api_key)])
async def start_interview_session(
    request: SessionStartRequest,
    db: AsyncSession = Depends(get_db),
):
    """
    يُنشئ جلسة مقابلة جديدة ويُعيد session_id + رابط WebRTC offer
    يُستدعى من Laravel عند بدء المقابلة
    """
    session_id = str(uuid.uuid4())

    return SessionStartResponse(
        session_id=session_id,
        webrtc_offer_url=f"/api/webrtc/offer",
        status="ready",
    )


@router.get("/session/{session_id}/status", dependencies=[Depends(verify_api_key)])
async def get_session_status(session_id: str):
    """
    حالة جلسة المقابلة — يُستدعى من Laravel للتحقق
    """
    from routers.webrtc import active_sessions
    session = active_sessions.get(session_id)
    if not session:
        return {"session_id": session_id, "status": "not_found"}
    return {
        "session_id": session_id,
        "status": "active",
        "window_count": session.window_index,
    }


@router.get("/questions", dependencies=[Depends(verify_api_key)])
async def get_suggested_questions(
    job_title: str = "general",
    language: str = "ar",
    limit: int = 10,
):
    """
    إرجاع أسئلة مقترحة من JSON ثابت (المرحلة 1 — بدون RAG)
    """
    from pathlib import Path
    import json

    questions_file = Path("/app/data/questions.json")
    if not questions_file.exists():
        return {"questions": _default_questions(language, limit)}

    with open(questions_file, "r", encoding="utf-8") as f:
        all_questions = json.load(f)

    filtered = [
        q for q in all_questions
        if q.get("language") == language and
           (q.get("job_title") == job_title or q.get("job_title") == "general")
    ][:limit]

    return {"questions": filtered}


def _default_questions(language: str, limit: int) -> list:
    """أسئلة افتراضية إذا لم يوجد ملف JSON"""
    ar_questions = [
        {"id": 1, "text": "حدثنا عن نفسك وخبراتك السابقة.", "category": "general"},
        {"id": 2, "text": "ما هي أبرز إنجازاتك في وظيفتك السابقة؟", "category": "achievement"},
        {"id": 3, "text": "كيف تتعامل مع ضغط العمل والمواعيد النهائية؟", "category": "behavioral"},
        {"id": 4, "text": "أين ترى نفسك بعد خمس سنوات؟", "category": "career"},
        {"id": 5, "text": "لماذا تريد الانضمام إلى شركتنا؟", "category": "motivation"},
        {"id": 6, "text": "صف موقفًا واجهت فيه تحديًا وكيف تغلبت عليه.", "category": "behavioral"},
        {"id": 7, "text": "ما هي نقاط قوتك الرئيسية؟", "category": "strengths"},
        {"id": 8, "text": "كيف تعمل ضمن فريق متنوع؟", "category": "teamwork"},
        {"id": 9, "text": "هل لديك أسئلة عن الوظيفة أو الشركة؟", "category": "closing"},
        {"id": 10, "text": "ما هي توقعاتك من هذه الوظيفة؟", "category": "expectations"},
    ]
    en_questions = [
        {"id": 1, "text": "Tell us about yourself and your previous experience.", "category": "general"},
        {"id": 2, "text": "What are your key achievements in your previous role?", "category": "achievement"},
        {"id": 3, "text": "How do you handle work pressure and deadlines?", "category": "behavioral"},
        {"id": 4, "text": "Where do you see yourself in five years?", "category": "career"},
        {"id": 5, "text": "Why do you want to join our company?", "category": "motivation"},
    ]
    questions = ar_questions if language == "ar" else en_questions
    return questions[:limit]
