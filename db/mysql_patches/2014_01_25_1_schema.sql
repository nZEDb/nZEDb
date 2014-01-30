DROP TRIGGER IF EXISTS check_insert;
DROP TRIGGER IF EXISTS check_update;

CREATE TRIGGER check_insert BEFORE INSERT ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.bitwise =((NEW.bitwise & ~512)|512);ELSEIF NEW.name REGEXP '^\\[[[:digit:]]+\\]' THEN SET NEW.bitwise = ((NEW.bitwise & ~1024)|1024); END IF; END;
CREATE TRIGGER check_update BEFORE UPDATE ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.bitwise =((NEW.bitwise & ~512)|512);ELSEIF NEW.name REGEXP '^\\[[[:digit:]]+\\]' THEN SET NEW.bitwise = ((NEW.bitwise & ~1024)|1024); END IF; END;

UPDATE releases SET bitwise = ((bitwise & ~1024)|1024) WHERE name REGEXP '^\\[[[:digit:]]+\\]';
UPDATE releases SET bitwise = ((bitwise & ~512)|512) WHERE name REGEXP '[a-fA-F0-9]{32}' OR searchname REGEXP '[a-fA-F0-9]{32}';

UPDATE site SET value = '171' WHERE setting = 'sqlpatch';
