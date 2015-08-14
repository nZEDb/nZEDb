# This patch adds back missing triggers
# from releases table
# it will be over fast

DELIMITER $$

DROP TRIGGER IF EXISTS check_insert $$
CREATE TRIGGER check_insert BEFORE INSERT ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1;ELSEIF NEW.name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\[' THEN SET NEW.isrequestid = 1; END IF; END;$$

DROP TRIGGER IF EXISTS check_update $$
CREATE TRIGGER check_update BEFORE UPDATE ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1;ELSEIF NEW.name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\[' THEN SET NEW.isrequestid = 1;END IF;END;$$

DROP TRIGGER IF EXISTS insert_search $$
CREATE TRIGGER insert_search AFTER INSERT ON releases FOR EACH ROW BEGIN INSERT INTO release_search_data (releaseid, guid, name, searchname) VALUES (NEW.id, NEW.guid, NEW.name, NEW.searchname);END;$$

DROP TRIGGER IF EXISTS update_search $$
CREATE TRIGGER update_search AFTER UPDATE ON releases FOR EACH ROW BEGIN IF NEW.guid != OLD.guid THEN UPDATE release_search_data SET guid = NEW.guid WHERE releaseid = OLD.id; END IF;IF NEW.name != OLD.name THEN UPDATE release_search_data SET name = NEW.name WHERE releaseid = OLD.id; END IF; IF NEW.searchname != OLD.searchname THEN UPDATE release_search_data SET searchname = NEW.searchname WHERE releaseid = OLD.id; END IF;END;$$

DROP TRIGGER IF EXISTS delete_search $$
CREATE TRIGGER delete_search AFTER DELETE ON releases FOR EACH ROW BEGIN DELETE FROM release_search_data WHERE releaseid = OLD.id; END;$$

DELIMITER ;

UPDATE releases SET ishashed = 1 WHERE name REGEXP '[a-fA-F0-9]{32}' AND ishashed = 0;
UPDATE releases SET isrequestid = 1 WHERE name REGEXP  '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\[' AND isrequestid = 0;
