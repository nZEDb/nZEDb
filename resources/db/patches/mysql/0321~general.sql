ALTER TABLE musicinfo ADD FULLTEXT INDEX ix_musicinfo_artist_title_ft (artist, title);
ALTER TABLE bookinfo ADD FULLTEXT INDEX ix_bookinfo_author_title_ft (author, title);
ALTER TABLE consoleinfo ADD FULLTEXT INDEX ix_consoleinfo_title_platform_ft (title, platform);