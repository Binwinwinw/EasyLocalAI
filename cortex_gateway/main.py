import os
import logging
import httpx
from fastapi import FastAPI, Request, Response, HTTPException, UploadFile, File
from fastapi.responses import StreamingResponse
from starlette.middleware.base import BaseHTTPMiddleware
import yaml
from typing import List, Dict, Any, Optional
import PyPDF2
from io import BytesIO

# Logging setup
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("cortex-gateway")

CORTEX_API_KEY = os.getenv("CORTEX_API_KEY", "")

class SecurityMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        # La santé est publique
        if request.url.path == "/health":
            return await call_next(request)
        
        # Vérification du jeton si configuré
        if CORTEX_API_KEY:
            token = request.headers.get("X-Cortex-Token")
            if token != CORTEX_API_KEY:
                logger.warning(f"Unauthorized access attempt from {request.client.host}")
                return Response(content="Unauthorized: Invalid or missing X-Cortex-Token", status_code=401)
        
        return await call_next(request)

app = FastAPI(title="Cortex Gateway V4 (Béton Armé)")
app.add_middleware(SecurityMiddleware)

# Configuration
CONFIG_PATH = os.getenv("CONFIG_PATH", "/app/gateway-config.yaml")
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY", "")
GROQ_API_KEY = os.getenv("GROQ_API_KEY", "")
OLLAMA_HOST = os.getenv("OLLAMA_HOST", "http://ollama_upstream:11434")

def load_config():
    try:
        if os.path.exists(CONFIG_PATH):
            with open(CONFIG_PATH, 'r') as f:
                return yaml.safe_load(f)
    except Exception as e:
        logger.error(f"Error loading config: {e}")
    return {
        "priority": ["ollama", "gemini", "groq"],
        "models": {
            "gemini": "gemini-1.5-flash",
            "groq": "llama-3.3-70b-versatile",
            "ollama_default": "llama3.2"
        }
    }

config = load_config()

async def get_client():
    return httpx.AsyncClient(timeout=httpx.Timeout(300.0, connect=5.0))

async def try_provider(provider: str, payload: Dict[str, Any]):
    """Generic provider attempt with failover support."""
    client = await get_client()
    url = ""
    headers = {"Content-Type": "application/json"}
    
    if provider == "native_ollama":
        # Native host (Windows/Mac) - often includes GPU
        url = "http://host.docker.internal:11434/v1/chat/completions"
        if payload.get("model") == "cortex":
            payload["model"] = config["models"].get("native_ollama_default", "llama3.2")
    elif provider == "ollama":
        url = f"{OLLAMA_HOST}/v1/chat/completions"
        if payload.get("model") == "cortex":
            payload["model"] = config["models"].get("ollama_default", "llama3.2")
    elif provider == "gemini":
        if not GEMINI_API_KEY: return None, None
        url = f"https://generativelanguage.googleapis.com/v1beta/openai/chat/completions?key={GEMINI_API_KEY}"
        payload["model"] = config["models"].get("gemini", "gemini-1.5-flash")
    elif provider == "groq":
        if not GROQ_API_KEY: return None, None
        url = "https://api.groq.com/openai/v1/chat/completions"
        payload["model"] = config["models"].get("groq", "llama-3.3-70b-versatile")
        headers["Authorization"] = f"Bearer {GROQ_API_KEY}"
    else:
        return None, None

    logger.info(f"Attempting {provider}...")
    try:
        if payload.get("stream"):
            # For streaming, we need to return the request context
            req = client.build_request("POST", url, json=payload, headers=headers)
            resp = await client.send(req, stream=True)
            if resp.status_code == 200:
                logger.info(f"{provider} SUCCESS (Stream)")
                return resp, client
            else:
                logger.warning(f"{provider} returned {resp.status_code}")
                await resp.aclose()
                await client.aclose()
        else:
            resp = await client.post(url, json=payload, headers=headers)
            if resp.status_code == 200:
                logger.info(f"{provider} SUCCESS (Bloquant)")
                return resp, client
            else:
                logger.warning(f"{provider} returned {resp.status_code}")
                await client.aclose()
    except Exception as e:
        logger.warning(f"{provider} FAILED: {e}")
        await client.aclose()
    
    return None, None

@app.get("/health")
async def health():
    return {"status": "ok", "version": "V4 (Béton Armé)"}

@app.get("/v1/native/models")
async def get_native_models():
    """Proxy to list models from the host's native Ollama."""
    try:
        async with httpx.AsyncClient(timeout=5.0) as client:
            resp = await client.get("http://host.docker.internal:11434/api/tags")
            if resp.status_code == 200:
                return resp.json()
    except Exception as e:
        logger.error(f"Could not reach host Ollama: {e}")
    return {"models": []}

def chunk_text(text: str, chunk_size: int = 1000, overlap: int = 200) -> List[str]:
    """Split text into chunks with overlap for better semantic context."""
    if len(text) <= chunk_size:
        return [text]
    
    chunks = []
    start = 0
    while start < len(text):
        end = start + chunk_size
        chunks.append(text[start:end])
        start += (chunk_size - overlap)
    return chunks

@app.post("/v1/rag/ingest")
async def rag_ingest(file: UploadFile = File(...)):
    """Parse PDF/TXT and return intelligent chunks."""
    content = ""
    filename = file.filename.lower()
    
    try:
        raw_data = await file.read()
        if filename.endswith(".pdf"):
            pdf_reader = PyPDF2.PdfReader(BytesIO(raw_data))
            for page in pdf_reader.pages:
                content += page.extract_text() + "\n"
        else:
            # Assume Text/Markdown
            content = raw_data.decode("utf-8")
        
        chunks = chunk_text(content)
        return {"filename": file.filename, "chunks": chunks, "count": len(chunks)}
    except Exception as e:
        logger.error(f"Ingestion error: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/v1/rag/embed")
async def rag_embed(request: Request):
    """Generate professional embeddings for chunks via Ollama."""
    body = await request.json()
    text = body.get("text", "")
    model = body.get("model", config["models"].get("ollama_default", "llama3.2"))
    
    async with httpx.AsyncClient(timeout=120.0) as client:
        resp = await client.post(
            f"{OLLAMA_HOST}/api/embed",
            json={"model": model, "input": text}
        )
        if resp.status_code == 200:
            data = resp.json()
            # Ollama /api/embed returns 'embeddings' for batch input
            return {"embedding": data.get("embeddings", [[]])[0]}
        
    raise HTTPException(status_code=500, detail="Embedding failure")

@app.api_route("/api/{path:path}", methods=["GET", "POST", "DELETE"])
async def proxy_ollama_api(path: str, request: Request):
    """Universal proxy for Ollama management API (/api/tags, /api/pull, etc.)"""
    client = await get_client()
    url = f"{OLLAMA_HOST}/api/{path}"
    
    # Prépare les arguments pour la requête proxy
    method = request.method
    headers = dict(request.headers)
    # On retire host pour éviter les conflits
    headers.pop("host", None)
    
    content = await request.body()
    
    try:
        # Pour le streaming (notamment /api/pull)
        req = client.build_request(method, url, content=content, headers=headers, params=request.query_params)
        resp = await client.send(req, stream=True)
        
        async def stream_generator():
            try:
                async for chunk in resp.aiter_bytes():
                    yield chunk
            finally:
                await resp.aclose()
                await client.aclose()

        return StreamingResponse(
            stream_generator(),
            status_code=resp.status_code,
            headers=dict(resp.headers)
        )
    except Exception as e:
        logger.error(f"Proxy error for /api/{path}: {e}")
        await client.aclose()
        raise HTTPException(status_code=502, detail=f"Ollama Proxy Error: {e}")

@app.post("/v1/chat/completions")
async def chat_completions(request: Request):
    body = await request.json()
    is_stream = body.get("stream", False)
    priority_list = config.get("priority", ["ollama", "gemini", "groq"])
    
    for provider in priority_list:
        try:
            resp, client = await try_provider(provider, body.copy())
            
            if resp:
                if is_stream:
                    async def stream_generator():
                        try:
                            async for chunk in resp.aiter_bytes():
                                yield chunk
                        except Exception as e:
                            logger.error(f"Streaming error from {provider}: {e}")
                        finally:
                            await resp.aclose()
                            await client.aclose()
                    
                    return StreamingResponse(
                        stream_generator(),
                        status_code=resp.status_code,
                        headers=dict(resp.headers)
                    )
                else:
                    content = await resp.aread()
                    headers = dict(resp.headers)
                    await resp.aclose()
                    await client.aclose()
                    return Response(content=content, status_code=resp.status_code, headers=headers)
        except Exception as e:
            logger.error(f"Provider {provider} failed critically: {e}")
            continue

    raise HTTPException(status_code=503, detail="Toutes les couches de l'IA sont hors ligne (Béton Fissuré)")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
