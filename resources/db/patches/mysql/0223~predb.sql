ALTER TABLE predb ADD COLUMN searched tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE predb ADD INDEX ix_predb_searched (searched);