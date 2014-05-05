DELETE FROM predb WHERE MD5(title) != MD5;
ALTER TABLE predb ADD COLUMN sha1 varchar(40) NOT NULL DEFAULT '';
ALTER TABLE predb MODIFY COLUMN md5 varchar(32) NOT NULL DEFAULT '';
UPDATE predb SET sha1 = sha1(title);
ALTER TABLE predb ADD UNIQUE INDEX ix_predb_sha1 (sha1);
