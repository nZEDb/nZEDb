-- This can be a long patch, you can check in mysql (mysql -p yourDatabaseName) what is going on
-- with this command: show processlist;

-- Truncating predb_hash table.
TRUNCATE TABLE predb_hashes;

-- Dropping primary key from predb_hash table.
ALTER TABLE predb_hashes DROP PRIMARY KEY;
-- Dropping the hashes column from the predb_hash table.
ALTER TABLE predb_hashes DROP COLUMN hashes;
-- Adding the hash column to the predb_hashes table.
ALTER TABLE predb_hashes ADD hash VARBINARY(20) FIRST;
-- Adding a primary key to the hash column.
ALTER TABLE predb_hashes ADD PRIMARY KEY(hash);

-- Creating hashes from predb titles, this can be long.
-- Stage 1: UNHEX(md5(title))
INSERT IGNORE INTO predb_hashes SELECT UNHEX(md5(title)), id from predb;
-- Stage 2: UNHEX(md5(md5(title)))
INSERT IGNORE INTO predb_hashes SELECT UNHEX(md5(md5(title))), id from predb;
-- Stage 3: UNHEX(sha1(title))
INSERT IGNORE INTO predb_hashes SELECT UNHEX(sha1(title)), id from predb;
