DROP TRIGGER IF EXISTS check_insert;
DROP TRIGGER IF EXISTS check_update;
DROP TRIGGER IF EXISTS check_rfinsert;
DROP TRIGGER IF EXISTS check_rfupdate;
DROP TRIGGER IF EXISTS insert_search;
DROP TRIGGER IF EXISTS update_search;
DROP TRIGGER IF EXISTS delete_search;
DROP TRIGGER IF EXISTS insert_hashes;
DROP TRIGGER IF EXISTS update_hashes;
DROP TRIGGER IF EXISTS delete_hashes;

CREATE TRIGGER check_insert BEFORE INSERT ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1; ELSEIF NEW.name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\[' THEN SET NEW.isrequestid = 1; END IF; END;

CREATE TRIGGER check_update BEFORE UPDATE ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1; ELSEIF NEW.name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\[' THEN SET NEW.isrequestid = 1; END IF; END;

CREATE TRIGGER check_rfinsert BEFORE INSERT ON releasefiles FOR EACH ROW BEGIN IF NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1; END IF; END;

CREATE TRIGGER check_rfupdate BEFORE UPDATE ON releasefiles FOR EACH ROW BEGIN IF NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1; END IF; END;

CREATE TRIGGER insert_search AFTER INSERT ON releases FOR EACH ROW BEGIN INSERT INTO releasesearch (releaseid, guid, name, searchname) VALUES (NEW.id, NEW.guid, NEW.name, NEW.searchname); END;

CREATE TRIGGER update_search AFTER UPDATE ON releases FOR EACH ROW BEGIN IF NEW.guid != OLD.guid THEN UPDATE releasesearch SET guid = NEW.guid WHERE releaseid = OLD.id; END IF; IF NEW.name != OLD.name THEN UPDATE releasesearch SET name = NEW.name WHERE releaseid = OLD.id; END IF; IF NEW.searchname != OLD.searchname THEN UPDATE releasesearch SET searchname = NEW.searchname WHERE releaseid = OLD.id; END IF; END;

CREATE TRIGGER delete_search AFTER DELETE ON releases FOR EACH ROW BEGIN DELETE FROM releasesearch WHERE releaseid = OLD.id; END;

CREATE TRIGGER insert_hashes AFTER INSERT ON predb FOR EACH ROW BEGIN INSERT INTO predbhash (pre_id, hashes) VALUES (NEW.id, CONCAT_WS(',', MD5(NEW.title), MD5(MD5(NEW.title)), SHA1(NEW.title))); END;

CREATE TRIGGER update_hashes AFTER UPDATE ON predb FOR EACH ROW BEGIN IF NEW.title != OLD.title THEN UPDATE predbhash SET hashes = CONCAT_WS(',', MD5(NEW.title), MD5(MD5(NEW.title)), SHA1(NEW.title)) WHERE pre_id = OLD.id; END IF; END;

CREATE TRIGGER delete_hashes AFTER DELETE ON predb FOR EACH ROW BEGIN DELETE FROM predbhash WHERE pre_id = OLD.id; END;