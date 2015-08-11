TRUNCATE TABLE predb_hashes;

ALTER TABLE predb_hashes DROP PRIMARY KEY;
ALTER TABLE predb_hashes DROP COLUMN hashes;
ALTER TABLE predb_hashes ADD hash VARBINARY(20) FIRST;
ALTER TABLE predb_hashes ADD PRIMARY KEY(hash);

INSERT IGNORE INTO predb_hashes SELECT UNHEX(md5(title)), id from predb;
INSERT IGNORE INTO predb_hashes SELECT UNHEX(md5(md5(title))), id from predb;
INSERT IGNORE INTO predb_hashes SELECT UNHEX(sha1(title)), id from predb;
