-- Add new columns to summaries table
ALTER TABLE summaries 
    ADD COLUMN IF NOT EXISTS video_url VARCHAR(500),
    ADD COLUMN IF NOT EXISTS video_id VARCHAR(50),
    ADD COLUMN IF NOT EXISTS video_title TEXT,
    ADD COLUMN IF NOT EXISTS thumbnail TEXT,
    ADD COLUMN IF NOT EXISTS duration INTEGER DEFAULT 0,
    ADD COLUMN IF NOT EXISTS original_text TEXT,
    ADD COLUMN IF NOT EXISTS summary_type VARCHAR(50),
    ADD COLUMN IF NOT EXISTS transcript_length INTEGER DEFAULT 0,
    ADD COLUMN IF NOT EXISTS processing_time FLOAT DEFAULT 0;

-- Rename columns if using old names
DO $$ 
BEGIN
    IF EXISTS(SELECT 1 FROM information_schema.columns 
              WHERE table_name='summaries' AND column_name='summary_text') THEN
        -- Column already named correctly
        NULL;
    ELSIF EXISTS(SELECT 1 FROM information_schema.columns 
                 WHERE table_name='summaries' AND column_name='summary') THEN
        ALTER TABLE summaries RENAME COLUMN summary TO summary_text;
    END IF;
END $$;

-- Update existing records
UPDATE summaries SET video_url = 'unknown' WHERE video_url IS NULL;

-- Add indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_summaries_video_url ON summaries(video_url);
CREATE INDEX IF NOT EXISTS idx_summaries_video_id ON summaries(video_id);
CREATE INDEX IF NOT EXISTS idx_summaries_created_at ON summaries(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_summaries_user_created ON summaries(user_id, created_at DESC);

-- Add text search index for video titles (PostgreSQL specific)
CREATE INDEX IF NOT EXISTS idx_summaries_title_search ON summaries USING gin(to_tsvector('english', video_title));