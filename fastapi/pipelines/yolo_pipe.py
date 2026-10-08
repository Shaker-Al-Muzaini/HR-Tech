"""
pipelines/yolo_pipe.py — كشف الكائنات (YOLOv8) — نسخة مُحسّنة
"""
import numpy as np
from ultralytics import YOLO
from models.schemas import ObjectIndicators
from core.config import settings
import threading

_model = None
_lock = threading.Lock()

# كائنات مثيرة للاهتمام
COCO_CLASSES_OF_INTEREST = {
    "cell phone": "phone",
    "book": "book",
    "laptop": "laptop",
    "cup": "cup",
    "remote": "remote",
    "keyboard": "keyboard",
    "mouse": "mouse",
    "tv": "tv",
    "tablet": "tablet",
}


def get_yolo_model(model_name: str = None) -> YOLO:
    global _model
    model_name = model_name or settings.YOLO_MODEL
    if _model is None:
        with _lock:
            if _model is None:
                print(f"📥 Loading YOLO model: {model_name}")
                _model = YOLO(model_name)
                print(f"✅ YOLO model loaded: {model_name}")
    return _model


def get_model() -> YOLO:
    return get_yolo_model()


def analyze_objects_frames(frames: list[np.ndarray]) -> ObjectIndicators:
    """
    كشف الكائنات مع تحسينات:
    - عيّنة كل 3 إطارات (بدلاً من 5)
    - conf=0.25 (بدلاً من 0.4)
    - تسجيل كل ما تم اكتشافه للتشخيص
    """
    if not frames:
        return ObjectIndicators(confidence=0.0)

    model = get_yolo_model()
    # عيّنة كل 3 إطارات
    sampled_frames = frames[::3] if len(frames) > 3 else frames

    detected_objects = set()
    phone_detected = False
    max_persons = 0
    all_detections = []

    for idx, frame in enumerate(sampled_frames):
        if frame is None:
            continue
        try:
            # ⬇️⬇️⬇️ إعدادات مُحسّنة للكشف
            results = model.predict(
                frame,
                conf=0.25,       # ← خُفّض من 0.4
                iou=0.5,         # ← معيار التداخل
                verbose=False,
                imgsz=640,       # ← دقة معالجة
            )

            for result in results:
                if result.boxes is None:
                    continue
                for box in result.boxes:
                    class_id = int(box.cls)
                    class_name = model.names[class_id]
                    confidence = float(box.conf)

                    # سجّل كل اكتشاف (للتشخيص)
                    all_detections.append(f"{class_name}({confidence:.2f})")

                    if class_name == "person":
                        max_persons = max(max_persons, 1)

                    if class_name in COCO_CLASSES_OF_INTEREST:
                        mapped = COCO_CLASSES_OF_INTEREST[class_name]
                        detected_objects.add(mapped)
                        if mapped == "phone":
                            phone_detected = True
                            print(f"📱 PHONE DETECTED (frame {idx}, conf={confidence:.2f})")

        except Exception as e:
            print(f"⚠️ YOLO frame error: {e}")
            continue

    # Logging للتشخيص
    if all_detections:
        unique = list(set(all_detections))
        print(f"🔍 YOLO detections: {unique[:10]}{'...' if len(unique) > 10 else ''}")

    # إذا اكتُشف شخص + هاتف = شخص يحمل هاتف
    if phone_detected:
        print(f"🚨 FINAL: Phone detected in window")

    return ObjectIndicators(
        objects_detected=list(detected_objects),
        phone_detected=phone_detected,
        person_count=max(1, max_persons),
        confidence=0.85 if sampled_frames else 0.0,
    )