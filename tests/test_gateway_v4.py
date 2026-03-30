import json
import httpx
import asyncio

async def test_gateway_health():
    async with httpx.AsyncClient() as client:
        try:
            # On suppose que le gateway tourne sur le port 8003 (mappé depuis 8000)
            resp = await client.get("http://localhost:8003/health")
            print(f"Health Check: {resp.status_code} - {resp.json()}")
        except Exception as e:
            print(f"Gateway not reachable on port 8003: {e}")

async def test_chat_streaming():
    url = "http://localhost:8003/v1/chat/completions"
    payload = {
        "model": "cortex",
        "messages": [{"role": "user", "content": "Hello, focus on the Hybrid V4 architecture."}],
        "stream": True
    }
    
    print("\nTesting Streaming...")
    async with httpx.AsyncClient(timeout=30.0) as client:
        try:
            async with client.stream("POST", url, json=payload) as resp:
                if resp.status_code != 200:
                    print(f"Error: {resp.status_code}")
                    return
                
                async for line in resp.aiter_lines():
                    if line:
                        print(f"Chunk: {line}")
        except Exception as e:
            print(f"Streaming failed: {e}")

if __name__ == "__main__":
    asyncio.run(test_gateway_health())
    asyncio.run(test_chat_streaming()) # Activé pour le test final
