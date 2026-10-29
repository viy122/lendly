"""Run real models on two supplied, non-sensitive single-face fixture photos."""

import argparse
from pathlib import Path
import tempfile

import cv2
import numpy as np

from compare import PhotoError, compare


def check(models, first, second):
    same = compare(first, first, models)
    different = compare(first, second, models)
    assert same > 0.95 and same > different + 0.2, (same, different)
    with tempfile.TemporaryDirectory() as directory:
        blank = Path(directory) / "blank.png"
        cv2.imwrite(str(blank), np.zeros((512, 512, 3), dtype=np.uint8))
        try:
            compare(first, blank, models)
            raise AssertionError("Blank selfie accepted")
        except PhotoError as error:
            assert str(error) == "selfie_face_count"
        image = cv2.imdecode(np.fromfile(str(first), dtype=np.uint8), cv2.IMREAD_COLOR)
        altered = Path(directory) / "altered.png"
        cv2.imwrite(str(altered), cv2.convertScaleAbs(image, alpha=0.9, beta=15))
        altered_score = compare(first, altered, models)
        assert altered_score > different + 0.2, (altered_score, different)
        multiple = Path(directory) / "multiple.png"
        cv2.imwrite(str(multiple), np.hstack([image, image]))
        try:
            compare(first, multiple, models)
            raise AssertionError("Multiple-face selfie accepted")
        except PhotoError as error:
            assert str(error) == "selfie_face_count"
    print(f"Real model checks passed: same={same:.4f}, different={different:.4f}; blank and multiple faces rejected")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("models", type=Path)
    parser.add_argument("first", type=Path)
    parser.add_argument("second", type=Path)
    args = parser.parse_args()
    check(args.models, args.first, args.second)
