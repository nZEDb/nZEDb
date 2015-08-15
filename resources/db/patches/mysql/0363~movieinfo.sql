-- Adds a column to store a trailer URL.
ALTER TABLE movieinfo ADD COLUMN trailer VARCHAR(255) NOT NULL DEFAULT '';