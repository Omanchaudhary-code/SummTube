import re
import yt_dlp
import logging
import os
import tempfile
import asyncio
import random
from typing import Dict, Optional, Callable, Any

logger = logging.getLogger(__name__)

class YouTubeService:
    async def _with_backoff(self, fn: Callable[[], Any], retries: int = 2, base_delay: float = 1.5):
        """
        Run a callable (sync or async) with small exponential backoff on 429/Too Many Requests.
        """
        for attempt in range(retries + 1):
            try:
                result = fn()
                if asyncio.iscoroutine(result):
                    result = await result
                return result
            except Exception as e:
                msg = str(e)
                if "429" not in msg and "Too Many Requests" not in msg:
                    raise
                if attempt == retries:
                    raise
                delay = base_delay * (2 ** attempt) + random.uniform(0, 0.5)
                logger.warning(f"Rate-limited by YouTube; retrying in {delay:.1f}s (attempt {attempt + 1}/{retries + 1})")
                await asyncio.sleep(delay)

    @staticmethod
    def extract_video_id(url: str) -> Optional[str]:
        """Extract video ID from various YouTube URL formats"""
        patterns = [
            r'(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)',
            r'youtube\.com\/watch\?.*v=([^&\n?#]+)'
        ]
        
        for pattern in patterns:
            match = re.search(pattern, url)
            if match:
                return match.group(1)
        return None
    
    def _get_cookies_file(self) -> Optional[str]:
        """
        Create a temporary file containing YouTube cookies from environment variable.
        Supports raw multi-line (YOUTUBE_COOKIES) or base64-encoded (YOUTUBE_COOKIES_B64).
        Returns the path to the temporary file or None if env var is missing.
        Caller is responsible for removing the file.
        """
        import base64

        # Prefer base64 if available
        cookies_b64 = os.getenv("YOUTUBE_COOKIES_B64")
        cookies_content = None

        if cookies_b64:
            try:
                cookies_content = base64.b64decode(cookies_b64).decode("utf-8", errors="replace")
                logger.info("Loaded YouTube cookies from YOUTUBE_COOKIES_B64")
            except Exception as e:
                logger.error(f"Failed to decode YOUTUBE_COOKIES_B64: {e}")
                cookies_content = None

        # Fallback to raw multi-line env var
        if not cookies_content:
            raw = os.getenv("YOUTUBE_COOKIES")
            if raw:
                # Strip wrapping quotes if present (some dashboards wrap multi-line values)
                if (raw.startswith('"') and raw.endswith('"')) or (raw.startswith("'") and raw.endswith("'")):
                    raw = raw[1:-1]
                cookies_content = raw
                logger.info("Loaded YouTube cookies from YOUTUBE_COOKIES")

        if not cookies_content:
            logger.info("No YouTube cookies provided via env; proceeding without cookies")
            return None

        try:
            # Create a named temp file that isn't deleted on close (so other libs can read it)
            fd, path = tempfile.mkstemp(suffix=".txt", text=True)
            with os.fdopen(fd, 'w') as f:
                # Ensure trailing newline (some parsers expect POSIX-style file end)
                if not cookies_content.endswith("\n"):
                    cookies_content += "\n"
                f.write(cookies_content)
            return path
        except Exception as e:
            logger.error(f"Failed to create cookies file: {str(e)}")
            return None

    async def get_video_data(self, video_url: str) -> Dict:
        """
        Fetch video metadata and transcript with multiple fallback mechanisms
        """
        cookie_file = None
        try:
            video_id = self.extract_video_id(video_url)
            if not video_id:
                raise ValueError("Invalid YouTube URL")
            
            logger.info(f"Processing video ID: {video_id}")
            
            # Setup Cookies
            cookie_file = self._get_cookies_file()
            if cookie_file:
                logger.info(f"Using provided YouTube cookies")

            transcript = None
            error_details = []

            # PHASE 1: Try YouTubeTranscriptApi (More reliable for transcripts)
            try:
                from youtube_transcript_api import YouTubeTranscriptApi
                logger.info(f"Attempting transcript extraction with YouTubeTranscriptApi for: {video_id}")
                
                def _list_and_pick():
                    # Get list of available transcripts (with cookies) and pick best English
                    transcript_list = YouTubeTranscriptApi.list_transcripts(video_id, cookies=cookie_file)
                    try:
                        t = transcript_list.find_manually_created_transcript(['en', 'en-US', 'en-GB'])
                        logger.info(f"Found manual English transcript: {t.language_code}")
                    except:
                        try:
                            t = transcript_list.find_generated_transcript(['en', 'en-US', 'en-GB'])
                            logger.info(f"Found generated English transcript: {t.language_code}")
                        except:
                            t = transcript_list.find_transcript(['en', 'en-US', 'en-GB'])
                            logger.info(f"Found some English transcript: {t.language_code}")
                    return t.fetch()

                try:
                    transcript_data = await self._with_backoff(_list_and_pick)
                    transcript = ' '.join([entry['text'] for entry in transcript_data])
                    logger.info("Transcript fetched successfully via YouTubeTranscriptApi")
                except Exception as e:
                    logger.warning(f"YouTubeTranscriptApi (list_transcripts) failed: {str(e)}")
                    # Fallback to direct get_transcript (also pass cookies) with backoff
                    transcript_data = await self._with_backoff(
                        lambda: YouTubeTranscriptApi.get_transcript(
                            video_id, languages=['en', 'en-US', 'en-GB'], cookies=cookie_file
                        )
                    )
                    transcript = ' '.join([entry['text'] for entry in transcript_data])
                    logger.info("Transcript fetched successfully via direct get_transcript")
            except Exception as e:
                error_details.append(f"YouTubeTranscriptApi failed: {str(e)}")
                logger.warning(f"YouTubeTranscriptApi failed: {str(e)}")

            # PHASE 2: Fetch Metadata (Title, Thumbnail, Duration)
            title = "YouTube Video"
            duration = 0
            thumbnail = f"https://img.youtube.com/vi/{video_id}/maxresdefault.jpg"
            
            # PHASE 3: Try yt-dlp for METADATA and TRANSCRIPT FALLBACK
            try:
                # Create a temporary directory for subtitle download
                with tempfile.TemporaryDirectory() as temp_dir:
                    out_tmpl = os.path.join(temp_dir, f"{video_id}_%(ext)s")
                    
                    ydl_opts = {
                        'quiet': True,
                        'no_warnings': True,
                        'skip_download': True, # We only want metadata and subs, not the video
                        'writesubtitles': not transcript,
                        'writeautomaticsub': not transcript,
                        'subtitleslangs': ['en'],
                        'outtmpl': out_tmpl,
                        'subtitlesformat': 'json3',
                        'user_agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'nocheckcertificate': True,
                        'geo_bypass': True,
                        # Gentle rate limiting to reduce 429s
                        'sleep_interval_requests': 1.0,
                        'max_sleep_interval_requests': 3.0,
                        'ratelimit': 500000,  # bytes per second (0.5 MB/s)
                        'concurrent_fragment_downloads': 1,
                    }
                    
                    # Add cookiefile if available
                    if cookie_file:
                        ydl_opts['cookiefile'] = cookie_file
                    
                    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                        # download=True is required to write subtitle files even if skip_download=True
                        info = ydl.extract_info(f"https://www.youtube.com/watch?v={video_id}", download=True)
                        title = info.get('title', title)
                        duration = info.get('duration', duration)
                        thumbnail = info.get('thumbnail', thumbnail)
                        
                        # If transcript still missing, try to read the downloaded subtitle file
                        if not transcript:
                            logger.info("Checking for downloaded subtitles in temp dir...")
                            
                            # Find best matching subtitle file
                            # yt-dlp naming: {video_id}.en.json3 or {video_id}.live_chat.json, etc.
                            sub_files = [f for f in os.listdir(temp_dir) if f.endswith('.json3')]
                            
                            # Prioritize manual English, then others
                            # Filenames usually: video_id.en.json3 (manual) or video_id.en-orig.json3
                            selected_file = None
                            
                            # 1. Manual English
                            for f in sub_files:
                                if f.endswith('.en.json3') and 'orig' not in f:
                                    selected_file = f
                                    break
                            
                            # 2. Any other English
                            if not selected_file and sub_files:
                                selected_file = sub_files[0]
                                
                            if selected_file:
                                sub_path = os.path.join(temp_dir, selected_file)
                                logger.info(f"Parsing subtitle file: {selected_file}")
                                transcript = self._parse_json3_subtitle(sub_path)
                                
            except Exception as e:
                error_details.append(f"yt-dlp metadata fetch failed: {str(e)}")
                logger.warning(f"yt-dlp failed: {str(e)}")

            # FINAL CHECK
            if not transcript:
                detailed_error = " | ".join(error_details)
                logger.error(f"No transcript found for {video_id}. Errors: {detailed_error}")
                
                # Check for specific error patterns
                if "Sign in to confirm" in detailed_error or "403" in detailed_error:
                    raise ValueError("YouTube blocked the request. This video may require sign-in or be restricted. Try using YOUTUBE_COOKIES environment variable or use a different video.")
                
                if "Subtitles are disabled" in detailed_error:
                    raise ValueError("This video has captions/subtitles disabled. Please try a different video with captions enabled.")
                
                if "429" in detailed_error or "Too Many Requests" in detailed_error:
                    raise ValueError("YouTube rate limit exceeded. Please try again in 15-20 minutes or use a different video link.")
                
                if "No transcripts were found" in detailed_error or "TranscriptsDisabled" in detailed_error:
                    raise ValueError("This video does not have captions/transcripts available. Please try a video with captions enabled.")
                
                if "Video unavailable" in detailed_error or "Private video" in detailed_error:
                    raise ValueError("This video is unavailable, private, or has been deleted.")
                
                # Generic error with helpful suggestions
                error_msg = "Could not fetch transcript for this video. "
                suggestions = []
                
                if not cookie_file:
                    suggestions.append("Try setting YOUTUBE_COOKIES environment variable for better access")
                
                suggestions.append("The video may have captions disabled")
                suggestions.append("The video may be age-restricted or region-locked")
                
                error_msg += "Possible reasons: " + "; ".join(suggestions) + "."
                raise ValueError(error_msg)
            
            return {
                "video_id": video_id,
                "title": title,
                "duration": duration,
                "thumbnail": thumbnail,
                "transcript": transcript
            }
            
        except ValueError:
            raise
        except Exception as e:
            logger.error(f"Error fetching video data: {str(e)}")
            raise ValueError(f"Failed to fetch video data: {str(e)}")
        finally:
            # Clean up cookie file
            if cookie_file and os.path.exists(cookie_file):
                try:
                    os.unlink(cookie_file)
                except Exception as e:
                    logger.warning(f"Failed to remove temp cookie file: {e}")
    
    def _parse_json3_subtitle(self, file_path: str) -> Optional[str]:
        """Parse text from a local json3 subtitle file"""
        try:
            import json
            with open(file_path, 'r', encoding='utf-8') as f:
                data = json.load(f)
                
            texts = []
            for event in data.get('events', []):
                if 'segs' in event:
                    for seg in event['segs']:
                        text = seg.get('utf8', '').strip()
                        if text:
                            texts.append(text)
            
            return ' '.join(texts)
        except Exception as e:
            logger.error(f"Error parsing subtitle file: {str(e)}")
            return None