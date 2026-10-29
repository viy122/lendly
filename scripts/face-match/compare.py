"""Local YuNet/SFace comparison. Emits only a score or a fixed error code."""

import argparse
import json
import math
import os
from pathlib import Path

os.environ.setdefault("OPENCV_LOG_LEVEL", "SILENT")
os.environ.setdefault("OPENCV_IO_MAX_IMAGE_PIXELS", "36000000")


class PhotoError(Exception):
    pass


def compare(front, selfie, models):
    import cv2
    import numpy as np

    cv2.setNumThreads(2)
    detector = cv2.FaceDetectorYN.create(
        str(models / "face_detection_yunet_2023mar.onnx"), "", (320, 320), 0.9, 0.3, 5000
    )
    recognizer = cv2.FaceRecognizerSF.create(
        str(models / "face_recognition_sface_2021dec.onnx"), ""
    )

    def feature(path, kind):
        try:
            data = np.fromfile(str(path), dtype=np.uint8)
            image = cv2.imdecode(data, cv2.IMREAD_COLOR)
        except (OSError, cv2.error):
            raise PhotoError(kind + "_invalid") from None
        if image is None or min(image.shape[:2]) < 200 or max(image.shape[:2]) > 6000:
            raise PhotoError(kind + "_invalid")
        scale = min(1.0, 1200 / max(image.shape[:2]))
        if scale < 1:
            image = cv2.resize(image, None, fx=scale, fy=scale, interpolation=cv2.INTER_AREA)
        detector.setInputSize((image.shape[1], image.shape[0]))
        _, faces = detector.detect(image)
        if faces is None or (kind == "selfie" and len(faces) != 1):
            raise PhotoError(kind + "_face_count")
        # IDs may repeat a smaller ghost portrait; compare the main portrait.
        face = max(faces, key=lambda item: item[2] * item[3])
        if min(face[2], face[3]) < 40:
            raise PhotoError(kind + "_face_count")
        aligned = recognizer.alignCrop(image, face)
        return recognizer.feature(aligned).copy()

    front_feature = feature(front, "front")
    selfie_feature = feature(selfie, "selfie")
    score = float(recognizer.match(front_feature, selfie_feature, cv2.FaceRecognizerSF_FR_COSINE))
    if not math.isfinite(score):
        raise RuntimeError("invalid_score")
    return max(-1.0, min(1.0, score))


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--front", required=True, type=Path)
    parser.add_argument("--selfie", required=True, type=Path)
    parser.add_argument("--models", required=True, type=Path)
    args = parser.parse_args()
    try:
        print(json.dumps({"similarity": compare(args.front, args.selfie, args.models)}, allow_nan=False))
    except PhotoError as error:
        print(json.dumps({"error": str(error)}))
        raise SystemExit(2)
    except Exception:
        print(json.dumps({"error": "unavailable"}))
        raise SystemExit(1)
