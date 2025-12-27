from fastapi import APIRouter, HTTPException
from app.models.schemas import SummarizeRequest, SummarizeResponse
from app.services.transcript import extract_transcript
from app.services.summarizer import summarize_text

router = APIRouter()


@router.post("/summarize", response_model=SummarizeResponse)
def summarize(data: SummarizeRequest):
    try:
        transcript = extract_transcript(
            source=data.source,
            source_type=data.source_type
        )

        summary = summarize_text(transcript)

        return SummarizeResponse(summary=summary)

    except ValueError as e:
        # bad input (wrong source_type)
        raise HTTPException(status_code=400, detail=str(e))

    except Exception as e:
        # internal server error
        raise HTTPException(status_code=500, detail=str(e))
