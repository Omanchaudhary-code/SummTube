from pydantic import BaseModel

class SummarizeRequest(BaseModel):
    source: str
    source_type: str  # text | youtube | url


class SummarizeResponse(BaseModel):
    summary: str
