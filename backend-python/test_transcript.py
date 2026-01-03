from youtube_transcript_api import YouTubeTranscriptApi

# Test different videos
video_ids = [
    'jNQXAC9IVRw',  # Me at the zoo
    'UF8uR6Z6KLc',  # TED talk
    '9bZkp7q19f0',  # Random
]

for video_id in video_ids:
    print(f"\n{'='*50}")
    print(f"Testing video: {video_id}")
    print('='*50)
    
    try:
        # List available transcripts
        transcript_list = YouTubeTranscriptApi.list_transcripts(video_id)
        
        print(f"Available transcripts:")
        for transcript in transcript_list:
            print(f"  - {transcript.language} ({transcript.language_code}) - Generated: {transcript.is_generated}")
        
        # Try to get English transcript
        transcript = YouTubeTranscriptApi.get_transcript(video_id)
        print(f"\n✅ SUCCESS! Got {len(transcript)} transcript entries")
        print(f"First entry: {transcript[0]}")
        
    except Exception as e:
        print(f"❌ ERROR: {str(e)}")
        print(f"Error type: {type(e).__name__}")
