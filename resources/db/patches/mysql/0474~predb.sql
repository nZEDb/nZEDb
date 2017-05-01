# Add FULLTEXT index on predb.filename. This patch might take some time to complete.

ALTER TABLE predb ADD FULLTEXT INDEX ix_predb_filename_ft (filename);
