DROP TRIGGER IF EXISTS check_rfinsert;
DROP TRIGGER IF EXISTS check_rfupdate;
CREATE TRIGGER check_rfinsert BEFORE INSERT ON release_files FOR EACH ROW BEGIN IF NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1; END IF; END;
CREATE TRIGGER check_rfupdate BEFORE UPDATE ON release_files FOR EACH ROW BEGIN IF NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1; END IF; END;
