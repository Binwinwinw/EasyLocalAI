import os
import json
import logging
import httpx
from fastapi import FastAPI, Request, Response, HTTPException
from fastapi.responses import StreamingResponse
import yaml
from typing import List, Dict, Any, Optional

# Logging setup
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("cortex-gateway")

app = FastAPI(title="Cortex Gateway V4 (Béton Armé)")

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

async def try_ollama(payload: Dict[str, Any]):
    """Attempts to proxy to local Ollama."""
    url = f"{OLLAMA_HOST}/v1/chat/completions"
    logger.info(f"Targeting Ollama: {url}")
    try:
        async with httpx.AsyncClient(timeout=30.0) as client:
            # We override the model if it's the gateway's default
            if payload.get("model") == "cortex":
                payload["model"] = config["models"].get("ollama_default", "llama3.2")
            
            response = await client.post(url, json=payload)
            if response.status_code == 200:
                logger.info("Ollama SUCCESS")
                return response
            else:
                logger.warning(f"Ollama returned {response.status_code}: {response.text}")
    except Exception as e:
        logger.warning(f"Ollama FAILED: {e}")
    return None

async def try_gemini(payload: Dict[str, Any]):
    """Attempts to proxy to Google Gemini 1.5 Flash."""
    if not GEMINI_API_KEY:
        logger.warning("Gemini API Key missing")
        return None
    
    url = f"https://generativelanguage.googleapis.com/v1beta/openai/chat/completions?key={GEMINI_API_KEY}"
    logger.info("Targeting Gemini Cloud")
    
    # Adapt payload for Gemini (OpenAI compatible endpoint)
    payload["model"] = config["models"].get("gemini", "gemini-1.5-flash")
    
    try:
        async with httpx.AsyncClient(timeout=30.0) as client:
            response = await client.post(url, json=payload)
            if response.status_code == 200:
                logger.info("Gemini SUCCESS")
                return response
            else:
                logger.warning(f"Gemini returned {response.status_code}: {response.text}")
    except Exception as e:
        logger.warning(f"Gemini FAILED: {e}")
    return None

async def try_groq(payload: Dict[str, Any]):
    """Attempts to proxy to Groq."""
    if not GROQ_API_KEY:
        logger.warning("Groq API Key missing")
        return None
    
    url = "https://api.groq.com/openai/v1/chat/completions"
    logger.info("Targeting Groq Cloud")
    
    payload["model"] = config["models"].get("groq", "llama-3.3-70b-versatile")
    
    headers = {
        "Authorization": f"Bearer {GROQ_API_KEY}",
        "Content-Type": "application/json"
    }
    
    try:
        async with httpx.AsyncClient(timeout=30.0) as client:
            response = await client.post(url, json=payload, headers=headers)
            if response.status_code == 200:
                logger.info("Groq SUCCESS")
                return response
            else:
                logger.warning(f"Groq returned {response.status_code}: {response.text}")
    except Exception as e:
        logger.warning(f"Groq FAILED: {e}")
    return None

@app.get("/health")
async def health():
    return {"status": "ok", "version": "V4 (Béton Armé)"}

@app.post("/v1/chat/completions")
async def chat_completions(request: Request):
    body = await request.json()
    is_stream = body.get("stream", False)
    
    # If streaming is requested, we need a different approach (pass-through)
    # For now, let's implement standard failover without full stream-proxying 
    # as it's complex for failover. But we will support the flag.
    
    priority_list = config.get("priority", ["ollama", "gemini", "groq"])
    
    for provider in priority_list:
        resp = None
        if provider == "ollama":
            resp = await try_ollama(body.copy())
        elif provider == "gemini":
            resp = await try_gemini(body.copy())
        elif provider == "groq":
            resp = await try_groq(body.copy())
            
        if resp:
            # Reconstruct the response
            return Response(
                content=resp.content,
                status_code=resp.status_code,
                headers=dict(resp.headers)
            )

    raise HTTPException(status_code=503, detail="Toutes les couches de l'IA sont hors ligne (Béton Fissuré)")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
