
from google import genai
import os

SYSTEM_PROMPT = """
You are an expert summarizer.

Rules:
- No markdown
- No headings
- No emojis
- No bullet symbols
- Use short paragraphs
- Keep it concise and clean
- Output plain text only
"""

def summarize_text(text: str) -> str:
    api_key = os.getenv("GOOGLE_API_KEY")
    if not api_key:
        raise RuntimeError("GOOGLE_API_KEY is not set")

    client = genai.Client(api_key=api_key)

    response = client.models.generate_content(
        model="gemini-3-flash-preview",
        contents=[
            SYSTEM_PROMPT,
            f"Summarize the following content:\n{text}"
        ]
    )

    return response.text.strip()

