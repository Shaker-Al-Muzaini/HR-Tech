# ─────────────────────────────────────────────────────────
# FastAPI Dockerfile — محرك الذكاء الاصطناعي
# ─────────────────────────────────────────────────────────
FROM python:3.11-slim

# تبعيات النظام: ffmpeg + libGL للمكتبات البصرية
RUN apt-get update && apt-get install -y \
    ffmpeg \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender-dev \
    libgomp1 \
    curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# نثبّت المتطلبات أولًا (cache layer)
COPY requirements.txt .
RUN pip install --default-timeout=1000 --no-cache-dir -r requirements.txt

# بعدها ننسخ باقي الكود
COPY . .

# تحميل نموذج Whisper عند البناء لتفادي التأخير أول مرة
RUN python -c "from faster_whisper import WhisperModel; WhisperModel('base', compute_type='int8')" || true

EXPOSE 8001

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--reload"]
