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
        Fetch video metadata and transcript using yt-dlp
        """
        try:
            video_id = self.extract_video_id(video_url)
            if not video_id:
                raise ValueError("Invalid YouTube URL")
            
            logger.info(f"Fetching data for video ID: {video_id}")
            
            # Use yt-dlp to get video metadata AND subtitles
            ydl_opts = {
                'quiet': True,
                'no_warnings': True,
                'skip_download': True,
                'writesubtitles': True,
                'writeautomaticsub': True,
                'subtitleslangs': ['en'],
                'subtitlesformat': 'json3',
            }
            
            with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                info = ydl.extract_info(f"https://www.youtube.com/watch?v={video_id}", download=False)
                
                title = info.get('title', 'Unknown')
                duration = info.get('duration', 0)
                thumbnail = info.get('thumbnail', '')
                
                # Extract subtitles
                subtitles = info.get('subtitles', {})
                automatic_captions = info.get('automatic_captions', {})
                
                transcript = None
                
                # Try manual subtitles first
                if 'en' in subtitles:
                    logger.info("Using manual English subtitles")
                    transcript = self._extract_text_from_subtitles(subtitles['en'])
                # Then try auto-generated
                elif 'en' in automatic_captions:
                    logger.info("Using auto-generated English captions")
                    transcript = self._extract_text_from_subtitles(automatic_captions['en'])
                
                if not transcript:
                    raise ValueError(f"No English subtitles or captions available for video: {video_id}")
                
                logger.info(f"Transcript extracted: {len(transcript)} characters")
            
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
                        response = requests.get(url)
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