from fastapi import APIRouter, HTTPException
from pydantic import BaseModel, Field
from typing import Optional
from app.services.youtube_service import YouTubeService
from app.services.ai_service import AIService
import logging

router = APIRouter()
logger = logging.getLogger(__name__)

class SummaryRequest(BaseModel):
    video_url: str = Field(..., description="YouTube video URL", example="https://www.youtube.com/watch?v=dQw4w9WgXcQ")
    summary_type: Optional[str] = Field(default="detailed", description="Type of summary: detailed, brief, or bullet_points")

class SummaryResponse(BaseModel):
    video_id: str
    title: str
    duration: int
    thumbnail: str
    summary: str
    transcript_length: int
    processing_time: float
    summary_type: str

@router.post("/summarize", response_model=SummaryResponse)
async def create_summary(request: SummaryRequest):
    """
    Generate AI-powered summary from YouTube video URL
    """
    try:
        logger.info(f"Processing video: {request.video_url}")
        
        # Fetch video data and transcript
        youtube_service = YouTubeService()
        video_data = await youtube_service.get_video_data(request.video_url)
        
        if not video_data:
            raise HTTPException(status_code=400, detail="Could not fetch video data")
        
        logger.info(f"Video data fetched: {video_data['title']}")
        
        # Generate AI summary
        ai_service = AIService()
        summary = await ai_service.generate_summary(
            transcript=video_data["transcript"],
            summary_type=request.summary_type,
            video_title=video_data["title"]
        )
        
        logger.info(f"Summary generated successfully")
        
        return SummaryResponse(
            video_id=video_data["video_id"],
            title=video_data["title"],
            duration=video_data["duration"],
            thumbnail=video_data["thumbnail"],
            summary=summary["text"],
            transcript_length=len(video_data["transcript"]),
            processing_time=summary["processing_time"],
            summary_type=request.summary_type
        )
        
    except ValueError as e:
        logger.error(f"Validation error: {str(e)}")
        raise HTTPException(status_code=400, detail=str(e))
    except Exception as e:
        logger.error(f"Error generating summary: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Failed to generate summary: {str(e)}")