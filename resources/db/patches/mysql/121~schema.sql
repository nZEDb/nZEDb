UPDATE releases SET reqidstatus = -1 WHERE reqidstatus = 0 AND nzbstatus = 1 AND relnamestatus IN (0, 1) AND name REGEXP '^\\[[[:digit:]]+\\]' = 0;

DROP TRIGGER hash_check_insert;
DROP TRIGGER hash_check_update;

CREATE TRIGGER check_insert BEFORE INSERT ON releases FOR EACH ROW BEGIN IF NEW.name REGEXP '^\\[[[:digit:]]+\\]' = 0 THEN SET NEW.reqidstatus = -1; ELSEIF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.hashed = true; END IF; END;
CREATE TRIGGER check_update BEFORE UPDATE ON releases FOR EACH ROW BEGIN IF NEW.name REGEXP '^\\[[[:digit:]]+\\]' = 0 THEN SET NEW.reqidstatus = -1; ELSEIF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.hashed = true; END IF; END;

UPDATE site SET value = '121' WHERE setting = 'sqlpatch';
