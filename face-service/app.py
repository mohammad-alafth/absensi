"""
Microservice face recognition (self-hosted).

Berjalan di server organisasi; data wajah TIDAK dikirim ke cloud.
Dipakai oleh aplikasi Laravel melalui:
    POST /embed  {"image": "<base64>"}  -> {"success": true, "embedding": [...], "quality": 0.0-1.0}
    POST /match  {"image": "<base64>", "embedding": [...]} -> {"success": true, "score": 0.0-1.0}

Model: InsightFace buffalo_l (ArcFace) versi ONNX, jalan di CPU.
"""

import base64
import io
import os

import numpy as np
from fastapi import FastAPI, Header, HTTPException
from PIL import Image
from insightface.app import FaceAnalysis

TOKEN = os.getenv("FACE_SERVICE_TOKEN", "").strip()
# Bawaan: buoyan,false sehingga mirip setting facebook/swift/arcface
DET_SIZE = int(os.getenv("FACE_DET_SIZE", "640"))
MIN_FACE_PX = int(os.getenv("FACE_MIN_PX", "120"))

# Folder model TIDAK memakai "/models" karena path itu tidak valid di Windows.
# Bawaan: folder di samping app.py. InsightFace sendiri membuat subfolder "models"
# di dalamnya, sehingga hasilnya face-service/models/buffalo_l/*.onnx (satu level).
MODEL_ROOT = os.getenv(
    "FACE_MODEL_ROOT",
    os.path.dirname(os.path.abspath(__file__)),
)

app = FastAPI(title="Absensi Face Service")

_analyzer = None


def analyzer() -> FaceAnalysis:
    global _analyzer
    if _analyzer is None:
        app_ = FaceAnalysis(name="buffalo_l", root=MODEL_ROOT)
        app_.prepare(ctx_id=-1, det_size=(DET_SIZE, DET_SIZE))
        _analyzer = app_
    return _analyzer


def decode_image(payload: str) -> np.ndarray:
    """Base64 -> array BGR.

    InsightFace (InsightFace.app.FaceAnalysis.get) memakai konvensi OpenCV,
    yaitu array numpy BGR. Meterai PIL akan memicu
    AttributeError: 'Image' object has no attribute 'shape'.
    """
    raw = payload.split(",", 1)[-1] if payload.startswith("data:") else payload
    raw = "".join(raw.split())
    try:
        img = Image.open(io.BytesIO(base64.b64decode(raw))).convert("RGB")
    except HTTPException:
        raise
    except Exception as exc:
        # Gambar rusak / bukan gambar: jawab 422, jangan 500 dengan traceback.
        raise HTTPException(status_code=422, detail=f"Gambar tidak dapat dibaca: {exc}")

    return np.asarray(img)[:, :, ::-1].copy()


def largest_face(img: np.ndarray):
    faces = analyzer().get(img)
    if not faces:
        return None
    # Ambil wajah terbesar supaya selfie jarak jauh tetap terbaca.
    return max(faces, key=lambda f: (f.bbox[2] - f.bbox[0]) * (f.bbox[3] - f.bbox[1]))


def face_quality(img: np.ndarray, face) -> float:
    """Kualitas 0..1: resolusi wajah + ketajaman sederhana (variance Laplacian)."""
    try:
        import cv2
        import numpy as np

        x1, y1, x2, y2 = [int(v) for v in face.bbox]
        crop = img[max(0, y1):y2, max(0, x1):x2]
        if crop.size == 0:
            return 0.0
        gray = cv2.cvtColor(crop, cv2.COLOR_BGR2GRAY)
        sharpness = min(1.0, cv2.Laplacian(gray, cv2.CV_64F).var() / 500.0)
        size_score = min(1.0, ((x2 - x1) * (y2 - y1)) / (200.0 * 200.0))
        return round(max(0.0, min(1.0, 0.6 * sharpness + 0.4 * size_score)), 3)
    except Exception:
        return 0.5


def authorize(token: str | None) -> None:
    if TOKEN and token != TOKEN:
        raise HTTPException(status_code=401, detail="Token tidak valid.")


@app.get("/health")
def health():
    try:
        analyzer()
        return {"success": True, "status": "ready", "model": "buffalo_l"}
    except Exception as exc:  # pragma: no cover
        return {"success": False, "status": "error", "error": str(exc)}


@app.post("/embed")
def embed(payload: dict, authorization: str | None = Header(default=None)):
    authorize(_bearer(authorization))

    image_b64 = payload.get("image")
    if not image_b64:
        raise HTTPException(status_code=422, detail="Parameter image wajib diisi.")

    img = decode_image(image_b64)
    face = largest_face(img)
    if face is None:
        return {"success": False, "error": "wajah_tidak_terdeteksi"}

    box_w = face.bbox[2] - face.bbox[0]
    box_h = face.bbox[3] - face.bbox[1]
    if min(box_w, box_h) < MIN_FACE_PX:
        return {"success": False, "error": "wajah_terlalu_kecil", "quality": 0.0}

    embedding = face.normed_embedding.astype(float).tolist()

    return {
        "success": True,
        "embedding": embedding,
        "quality": face_quality(img, face),
        "dimensions": len(embedding),
    }


@app.post("/match")
def match(payload: dict, authorization: str | None = Header(default=None)):
    authorize(_bearer(authorization))

    image_b64 = payload.get("image")
    reference = payload.get("embedding")
    if not image_b64 or not reference:
        raise HTTPException(status_code=422, detail="Parameter image dan embedding wajib diisi.")

    face = largest_face(decode_image(image_b64))
    if face is None:
        return {"success": False, "error": "wajah_tidak_terdeteksi", "score": None}

    probe = face.normed_embedding.astype(float)
    ref = np.asarray(reference, dtype=float)
    if ref.shape != probe.shape:
        return {"success": False, "error": "dimensi_vektor_tidak_cocok", "score": None}

    ref_norm = ref / (np.linalg.norm(ref) or 1.0)
    score = float(np.dot(probe, ref_norm))

    return {"success": True, "score": round(score, 4), "detected": True}


def _bearer(authorization: str | None) -> str | None:
    if authorization and authorization.lower().startswith("bearer "):
        return authorization[7:].strip()
    return authorization