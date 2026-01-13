import logging
import os
import time
from typing import Dict

from groq import AsyncGroq

logger = logging.getLogger(__name__)


class AIService:
    def __init__(self):
        # GROQ setup
        self.api_key = os.getenv("GROQ_API_KEY")
        if not self.api_key:
            raise ValueError("GROQ_API_KEY not found in environment variables")

        self.model = os.getenv("GROQ_MODEL", "llama-3.3-70b-versatile")
        self.temperature = float(os.getenv("GROQ_TEMPERATURE", "0.5"))
        # Groq client expects max_tokens
        self.max_tokens = int(os.getenv("GROQ_MAX_TOKENS", "1024"))

        self.client = AsyncGroq(api_key=self.api_key)
        logger.info(f"Groq client initialized with model {self.model}")

    async def generate_summary(
        self,
        transcript: str,
        summary_type: str = "detailed",
        video_title: str = "",
    ) -> Dict:
        """Generate AI summary of video transcript using Groq"""
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
{transcript}""",
        }

        prompt = prompts.get(summary_type, prompts["detailed"])

        try:
            logger.info(f"Generating {summary_type} summary with Groq ({self.model})...")

            response = await self.client.chat.completions.create(
                model=self.model,
                messages=[
                    {
                        "role": "system",
                        "content": "You are a helpful assistant that produces concise, clean plain-text summaries without markdown or special formatting.",
                    },
                    {"role": "user", "content": prompt},
                ],
                temperature=self.temperature,
                max_tokens=self.max_tokens,
                top_p=1,
                stream=False,
            )

            summary_text = response.choices[0].message.content

            processing_time = time.time() - start_time
            logger.info(f"Summary generated in {processing_time:.2f}s")

            return {
                "text": summary_text,
                "processing_time": round(processing_time, 2),
            }

        except Exception as e:
            # Groq returns httpx errors with status code inside message; check for rate limits
            error_str = str(e)
            if "429" in error_str or "rate limit" in error_str.lower():
                logger.error(f"AI Rate Limit Reached: {error_str}")
                raise ResourceWarning(f"AI Service overloaded (429): {error_str}")

            logger.error(f"AI generation error: {error_str}")
            raise ValueError(f"Failed to generate summary: {error_str}")