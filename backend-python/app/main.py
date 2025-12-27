from fastapi import FastAPI
from app.api.summarize import router as summarize_router
from dotenv import load_dotenv
import os

load_dotenv()
print("GOOGLE_API_KEY =", os.getenv("GOOGLE_API_KEY"))
app = FastAPI(
    title="AI Summarization Service",
    version="1.0.0"
)

app.include_router(
    summarize_router,
    prefix="/api",
    tags=["Summarization"]
)

@app.get("/")
def root():
    return {"status": "AI service running"}
