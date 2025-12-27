import yt_dlp
import time
import re
from urllib.request import urlopen
from urllib.error import HTTPError, URLError
import socket


def extract_transcript(source: str, source_type: str) -> str:
    """
    source_type: text | youtube | url
    """

    if source_type == "text":
        return source

    if source_type == "youtube":
        return _youtube_transcript(source)

    if source_type == "url":
        return f"Content extracted from {source}"

    raise ValueError("Unsupported source type")


def _youtube_transcript(url: str) -> str:
    ydl_opts = {
        "quiet": True,
        "skip_download": True,
        "writesubtitles": True,
        "writeautomaticsub": True
    }

    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=False)
    except Exception:
        raise Exception("YouTube rate limit exceeded. Please try again later.")

    # Prefer manual subtitles
    subtitles = info.get("subtitles") or info.get("automatic_captions")

    if not subtitles:
        raise Exception("No subtitles available for this video")

    # Prefer English explicitly
    lang_tracks = (
        subtitles.get("en")
        or subtitles.get("en-US")
        or next(iter(subtitles.values()), None)
    )

    if not lang_tracks:
        raise Exception("No subtitle tracks found")

    vtt_url = lang_tracks[0].get("url")
    if not vtt_url:
        raise Exception("Invalid subtitle track")

    return _download_and_clean_subtitles(vtt_url)


def _download_and_clean_subtitles(vtt_url: str, retries: int = 3) -> str:
    for attempt in range(retries):
        try:
            with urlopen(vtt_url, timeout=10) as response:
                text = response.read().decode("utf-8")
            break

        except (HTTPError, URLError, socket.timeout) as e:
            if attempt < retries - 1:
                time.sleep(2 ** attempt)
            else:
                raise Exception(
                    "YouTube rate limit exceeded. Please try again later."
                )

    # Clean subtitle text
    text = re.sub(r"\d{2}:\d{2}:\d{2}\.\d+ --> .*", "", text)
    text = re.sub(r"WEBVTT|Kind:.*|Language:.*", "", text)
    text = re.sub(r"<[^>]+>", "", text)

    lines = [line.strip() for line in text.splitlines() if line.strip()]
    return " ".join(lines)
