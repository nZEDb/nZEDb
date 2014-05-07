ALTER TABLE predb ADD COLUMN filename varchar(255) NOT NULL DEFAULT '';
ALTER TABLE predb ADD INDEX ix_predb_filename (filename);

ALTER TABLE releasefiles ADD COLUMN ishashed tinyint(1) NOT NULL DEFAULT 0 AFTER size;
ALTER TABLE releasefiles ADD INDEX ix_releasefiles_ishashed (ishashed);

DROP TRIGGER IF EXISTS check_rfinsert;
DROP TRIGGER IF EXISTS check_rfupdate;

CREATE TRIGGER check_rfinsert BEFORE INSERT ON releasefiles FOR EACH ROW BEGIN IF NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1; END IF; END;
CREATE TRIGGER check_rfupdate BEFORE UPDATE ON releasefiles FOR EACH ROW BEGIN IF NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1; END IF; END;

UPDATE releasefiles SET ishashed = 1 WHERE name REGEXP '[a-fA-F0-9]{32}';
