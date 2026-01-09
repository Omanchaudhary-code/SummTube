import re
import yt_dlp
import logging
from typing import Dict, Optional

logger = logging.getLogger(__name__)

class YouTubeService:
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
    
    async def get_video_data(self, video_url: str) -> Dict:
        """
        Fetch video metadata and transcript with multiple fallback mechanisms
        """
        try:
            video_id = self.extract_video_id(video_url)
            if not video_id:
                raise ValueError("Invalid YouTube URL")
            
            logger.info(f"Processing video ID: {video_id}")
            
            transcript = None
            error_details = []

            # PHASE 1: Try YouTubeTranscriptApi (More reliable for transcripts)
            try:
                from youtube_transcript_api import YouTubeTranscriptApi
                logger.info(f"Attempting transcript extraction with YouTubeTranscriptApi for: {video_id}")
                
                try:
                    # Get list of available transcripts
                    transcript_list = YouTubeTranscriptApi.list_transcripts(video_id)
                    
                    # Try to find best English transcript
                    try:
                        # 1. Manual English
                        t = transcript_list.find_manually_created_transcript(['en', 'en-US', 'en-GB'])
                        logger.info(f"Found manual English transcript: {t.language_code}")
                    except:
                        try:
                            # 2. Generated English
                            t = transcript_list.find_generated_transcript(['en', 'en-US', 'en-GB'])
                            logger.info(f"Found generated English transcript: {t.language_code}")
                        except:
                            # 3. Any English (might be translated)
                            t = transcript_list.find_transcript(['en', 'en-US', 'en-GB'])
                            logger.info(f"Found some English transcript: {t.language_code}")
                    
                    transcript_data = t.fetch()
                    transcript = ' '.join([entry['text'] for entry in transcript_data])
                    logger.info("Transcript fetched successfully via YouTubeTranscriptApi")
                except Exception as e:
                    logger.warning(f"YouTubeTranscriptApi (list_transcripts) failed: {str(e)}")
                    # Fallback to direct get_transcript
                    transcript_data = YouTubeTranscriptApi.get_transcript(video_id, languages=['en', 'en-US', 'en-GB'])
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
                ydl_opts = {
                    'quiet': True,
                    'no_warnings': True,
                    'skip_download': True,
                    'writesubtitles': not transcript,
                    'writeautomaticsub': not transcript,
                    'subtitleslangs': ['en'],
                    'user_agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'nocheckcertificate': True,
                    'geo_bypass': True,
                }
                
                with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                    info = ydl.extract_info(f"https://www.youtube.com/watch?v={video_id}", download=False)
                    title = info.get('title', title)
                    duration = info.get('duration', duration)
                    thumbnail = info.get('thumbnail', thumbnail)
                    
                    # If transcript still missing, try to extract from yt-dlp info
                    if not transcript:
                        subtitles = info.get('subtitles', {})
                        automatic_captions = info.get('automatic_captions', {})
                        
                        target_subs = subtitles.get('en') or automatic_captions.get('en')
                        if target_subs:
                            logger.info("Attempting transcript extraction from yt-dlp subtitle tracks")
                            transcript = self._extract_text_from_subtitles(target_subs)
            except Exception as e:
                error_details.append(f"yt-dlp metadata fetch failed: {str(e)}")
                logger.warning(f"yt-dlp failed: {str(e)}")

            # FINAL CHECK
            if not transcript:
                detailed_error = " | ".join(error_details)
                logger.error(f"No transcript found for {video_id}. Errors: {detailed_error}")
                
                if "Sign in to confirm you're not a bot" in detailed_error or "403" in detailed_error or "429" in detailed_error or "Too Many Requests" in detailed_error:
                    raise ValueError("YouTube blocked the request (Bot detected). Please try again in 15-20 minutes or use a different video link.")
                
                raise ValueError("Could not fetch transcript for this video. It may have captions disabled or be age-restricted.")
            
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
    
    def _extract_text_from_subtitles(self, subtitle_tracks):
        """Extract text from subtitle tracks"""
        try:
            # Find json3 format
            for track in subtitle_tracks:
                if track.get('ext') == 'json3':
                    url = track.get('url')
                    if url:
                        import requests
                        headers = {
                            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
                        }
                        response = requests.get(url, headers=headers, timeout=10)
                        data = response.json()
                        
                        # Extract text from events
                        texts = []
                        for event in data.get('events', []):
                            if 'segs' in event:
                                for seg in event['segs']:
                                    text = seg.get('utf8', '').strip()
                                    if text:
                                        texts.append(text)
                        
                        return ' '.join(texts)
            
            return None
        except Exception as e:
            logger.error(f"Error extracting subtitle text: {str(e)}")
            return None