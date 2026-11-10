"""
BrnoAkce Vector Embedding Microservice
Model: sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2
Engine: ONNX Runtime via FastEmbed
"""
import time
from typing import List
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from fastembed import TextEmbedding

app = FastAPI(title="BrnoAkce Embedding Service", version="1.0.0")

MODEL_NAME = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
model: TextEmbedding = None


class EmbedRequest(BaseModel):
    text: str


class EmbedResponse(BaseModel):
    vector: List[float]
    dim: int
    took_ms: float


class BatchEmbedRequest(BaseModel):
    texts: List[str]


class BatchEmbedResponse(BaseModel):
    vectors: List[List[float]]
    dim: int
    took_ms: float


@app.on_event("startup")
def load_model():
    global model
    print(f"Loading FastEmbed model: {MODEL_NAME}...")
    t0 = time.time()
    model = TextEmbedding(model_name=MODEL_NAME)
    print(f"Model loaded in {time.time() - t0:.2f}s")


@app.get("/health")
def health():
    return {
        "status": "ok",
        "model": MODEL_NAME,
        "dim": 384,
        "loaded": model is not None,
    }


@app.post("/embed", response_model=EmbedResponse)
def embed_single(req: EmbedRequest):
    if not model:
        raise HTTPException(status_code=503, detail="Model not loaded yet")
    
    text = (req.text or "").strip()
    if not text:
        raise HTTPException(status_code=400, detail="Text cannot be empty")
    
    t0 = time.time()
    embeddings = list(model.embed([text]))
    vector = [float(x) for x in embeddings[0]]
    took_ms = round((time.time() - t0) * 1000, 2)

    return EmbedResponse(vector=vector, dim=len(vector), took_ms=took_ms)


@app.post("/embed-batch", response_model=BatchEmbedResponse)
def embed_batch(req: BatchEmbedRequest):
    if not model:
        raise HTTPException(status_code=503, detail="Model not loaded yet")
    
    if not req.texts:
        return BatchEmbedResponse(vectors=[], dim=384, took_ms=0.0)
    
    t0 = time.time()
    embeddings = list(model.embed(req.texts))
    vectors = [[float(x) for x in emb] for emb in embeddings]
    took_ms = round((time.time() - t0) * 1000, 2)

    return BatchEmbedResponse(vectors=vectors, dim=384, took_ms=took_ms)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("embed_service:app", host="127.0.0.1", port=8088, log_level="info")
