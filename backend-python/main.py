# Module: main.py
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.routes.summary import router as summary_router
from app.utils.logger import setup_logging
from app.middleware.request_id import RequestIDMiddleware
import uvicorn
import os

# Load environment variables FIRST
load_dotenv() 

# Setup logging
setup_logging()

# Initialize FastAPI app
app = FastAPI(
    title="SummTube AI Service",
    description="YouTube Video Summarization API with AI",
    version="1.0.0"
)

# Add request ID middleware (first, so it applies to all requests)
app.add_middleware(RequestIDMiddleware)

# CORS middleware - use env variable
# Production: MUST be set via ALLOWED_ORIGINS env var (no localhost defaults)
# Development: Defaults to localhost for local development
env = os.getenv("APP_ENV", "development")
if env == "production":
    allowed_origins_raw = os.getenv("ALLOWED_ORIGINS", "")
    if not allowed_origins_raw:
        import logging
        logging.warning("ALLOWED_ORIGINS not set in production. CORS may not work correctly.")
    allowed_origins = [origin.strip() for origin in allowed_origins_raw.split(",") if origin.strip()] if allowed_origins_raw else []
else:
    allowed_origins_raw = os.getenv("ALLOWED_ORIGINS", "http://localhost:3000,http://localhost:8080")
    allowed_origins = [origin.strip() for origin in allowed_origins_raw.split(",") if origin.strip()]

app.add_middleware(
    CORSMiddleware,
    allow_origins=allowed_origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Health check endpoints
@app.get("/")
async def root():
    return {
        "status": "healthy",
        "service": "SummTube AI Service",
        "version": "2.0.0",
        "endpoints": {
            "health": "/api/v1/health",
            "summarize": "/api/v1/summarize"
        }
    }

# Explicit HEAD handlers (no body)
from fastapi import Response
@app.head("/")
async def root_head():
    return Response(status_code=200)

@app.get("/api/v1/health")
async def health_check():
    return {
        "status": "healthy",
        "service": "SummTube AI Service",
        "api_key_loaded": bool(os.getenv("GOOGLE_API_KEY"))  # Check if key is loaded
    }

@app.head("/api/v1/health")
async def health_head():
    return Response(status_code=200)

# Include routers
app.include_router(summary_router, prefix="/api/v1", tags=["summarization"])

if __name__ == "__main__":
    host = os.getenv("HOST", "0.0.0.0")
    port = int(os.getenv("PORT", 8001))
    debug = os.getenv("DEBUG", "False").lower() == "true"
    
    uvicorn.run("main:app", host=host, port=port, reload=debug)