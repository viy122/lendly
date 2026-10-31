"""Download official OpenCV Zoo weights, checking their published SHA-256 IDs."""

import argparse
import hashlib
from pathlib import Path
import urllib.request

MODELS = {
    "face_detection_yunet/face_detection_yunet_2023mar.onnx":
        "8f2383e4dd3cfbb4553ea8718107fc0423210dc964f9f4280604804ed2552fa4",
    "face_recognition_sface/face_recognition_sface_2021dec.onnx":
        "0ba9fbfa01b5270c96627c4ef784da859931e02f04419c829e83484087c34e79",
}


def install(destination):
    destination.mkdir(parents=True, exist_ok=True)
    for model, expected in MODELS.items():
        target = destination / Path(model).name
        if not target.exists() or hashlib.sha256(target.read_bytes()).hexdigest() != expected:
            temporary = target.with_suffix(".download")
            try:
                with urllib.request.urlopen(
                    "https://media.githubusercontent.com/media/opencv/opencv_zoo/main/models/" + model,
                    timeout=120,
                ) as response, temporary.open("wb") as output:
                    while chunk := response.read(1024 * 1024):
                        output.write(chunk)
                if hashlib.sha256(temporary.read_bytes()).hexdigest() != expected:
                    raise RuntimeError("Model checksum mismatch: " + target.name)
                temporary.replace(target)
            finally:
                temporary.unlink(missing_ok=True)
        print("Verified " + target.name)
        folder = model.split('/')[0]
        license_path = destination / (folder + "-LICENSE.txt")
        if not license_path.exists():
            with urllib.request.urlopen(
                "https://raw.githubusercontent.com/opencv/opencv_zoo/main/models/" + folder + "/LICENSE",
                timeout=30,
            ) as response:
                license_path.write_bytes(response.read())


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("destination", type=Path)
    install(parser.parse_args().destination)
