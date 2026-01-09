from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.routes.summary import router as summary_router
from app.utils.logger import setup_logging
from dotenv import load_dotenv  # ADD THIS
import uvicorn
import os

# Load environment variables FIRST
load_dotenv()  # ADD THIS

# Setup logging
setup_logging()

# Initialize FastAPI app
app = FastAPI(
    title="SummTube AI Service",
    description="YouTube Video Summarization API with AI",
    version="2.0.0"
)

# CORS middleware - use env variable
allowed_origins_raw = os.getenv("ALLOWED_ORIGINS", "http://localhost:3000,http://localhost:5173,https://summtube.vercel.app")
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

@app.get("/api/v1/health")
async def health_check():
    return {
        "status": "healthy",
        "service": "SummTube AI Service",
        "api_key_loaded": bool(os.getenv("GOOGLE_API_KEY"))  # Check if key is loaded
    }

# Include routers
app.include_router(summary_router, prefix="/api/v1", tags=["summarization"])

if __name__ == "__main__":
    host = os.getenv("HOST", "0.0.0.0")
    port = int(os.getenv("PORT", 8001))
    debug = os.getenv("DEBUG", "False").lower() == "true"
    
    uvicorn.run("main:app", host=host, port=port, reload=debug)