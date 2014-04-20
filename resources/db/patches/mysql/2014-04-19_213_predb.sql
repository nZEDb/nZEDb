ALTER TABLE predb ADD COLUMN sha1 varchar(40) NOT NULL DEFAULT '0';
ALTER TABLE predb MODIFY COLUMN md5 varchar(32) NOT NULL DEFAULT '0';
UPDATE predb SET sha1 = sha1(title);
ALTER TABLE predb ADD UNIQUE INDEX ix_predb_sha1 (sha1);
