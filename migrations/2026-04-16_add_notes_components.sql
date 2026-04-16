-- Add component-grade columns to existing notes table without data loss
ALTER TABLE notes
    ADD COLUMN IF NOT EXISTS td DECIMAL(4,2) NULL,
    ADD COLUMN IF NOT EXISTS tp DECIMAL(4,2) NULL,
    ADD COLUMN IF NOT EXISTS exam DECIMAL(4,2) NULL,
    ADD COLUMN IF NOT EXISTS moyenne DECIMAL(4,2) NULL;

-- Backfill moyenne from note for existing rows
UPDATE notes
SET moyenne = note
WHERE moyenne IS NULL AND note IS NOT NULL;
