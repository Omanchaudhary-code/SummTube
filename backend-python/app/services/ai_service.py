import os
import time
from typing import Dict
import google.generativeai as genai
import logging

logger = logging.getLogger(__name__)

class AIService:
    def __init__(self):
        self.api_key = os.getenv("GOOGLE_API_KEY")
        if not self.api_key:
            raise ValueError("GOOGLE_API_KEY not found in environment variables")
        
        # Configure the API
        genai.configure(api_key=self.api_key)
        
        # Use gemini-2.5-flash (confirmed by user)
        self.model = genai.GenerativeModel('gemini-2.5-flash')
        logger.info("Gemini AI initialized successfully with gemini-2.5-flash")
    
    async def generate_summary(
        self, 
        transcript: str, 
        summary_type: str = "detailed",
        video_title: str = ""
    ) -> Dict:
        """Generate AI summary of video transcript"""
        start_time = time.time()
        
        prompts = {
            "detailed": f"""Provide a comprehensive summary of this YouTube video titled "{video_title}" in plain text format without any markdown, bold text, or special formatting.

Include:
- Main topic and key points
- Important details and examples
- Conclusions or takeaways

Write in clear paragraphs using only plain text.

Transcript:
{transcript}""",

            "brief": f"""Provide a concise 2-3 paragraph summary of this YouTube video titled "{video_title}" in plain text format without any markdown, bold text, or special formatting.

Focus on the main message and key takeaways only. Write in clear paragraphs using only plain text.

Transcript:
{transcript}""",

            "bullet_points": f"""Summarize this YouTube video titled "{video_title}" as bullet points in plain text format without any markdown or special formatting.

Format:
- Main topic
- Key points (3-5 bullets)
- Important takeaways

Use simple dashes (-) for bullet points, no special characters.

Transcript:
{transcript}"""
        }
        
        prompt = prompts.get(summary_type, prompts["detailed"])
        
        try:
            logger.info(f"Generating {summary_type} summary with Gemini...")
            
            # Generate content using the model
            response = self.model.generate_content(prompt)
            summary_text = response.text
            
            processing_time = time.time() - start_time
            logger.info(f"Summary generated in {processing_time:.2f}s")
            
            return {
                "text": summary_text,
                "processing_time": round(processing_time, 2)
            }
            
        except Exception as e:
            logger.error(f"AI generation error: {str(e)}")
            raise ValueError(f"Failed to generate summary: {str(e)}")