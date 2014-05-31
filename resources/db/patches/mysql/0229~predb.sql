ALTER TABLE predb DROP INDEX ix_predb_title;
ALTER IGNORE TABLE predb ADD UNIQUE INDEX ix_predb_title (title);
