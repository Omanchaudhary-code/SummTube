try:
    from youtube_transcript_api import YouTubeTranscriptApi
    
    print("Library imported successfully!")
    print(f"Module: {YouTubeTranscriptApi}")
    print(f"Has get_transcript: {hasattr(YouTubeTranscriptApi, 'get_transcript')}")
    
    # Test with a known working video
    video_id = "dQw4w9WgXcQ"
    transcript = YouTubeTranscriptApi.get_transcript(video_id)
    print(f"Success! Got {len(transcript)} transcript segments")
    
except ImportError as e:
    print(f"Import error: {e}")
except Exception as e:
    print(f"Error: {e}")