# Add fulltext index to gamesinfo title column

ALTER TABLE gamesinfo ADD FULLTEXT INDEX ix_title_ft (title);
