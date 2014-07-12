ALTER TABLE binaries ADD COLUMN currentparts INT UNSIGNED NOT NULL DEFAULT '0' AFTER totalparts;
UPDATE binaries b SET currentparts = (SELECT COUNT(*) FROM parts p WHERE p.binaryid = b.id);