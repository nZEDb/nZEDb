DROP TRIGGER IF EXISTS insert_search;
DROP TRIGGER IF EXISTS update_search;
DROP TRIGGER IF EXISTS delete_search;
RENAME TABLE releasesearch TO release_search_data;
CREATE TRIGGER insert_search AFTER INSERT ON releases FOR EACH ROW BEGIN INSERT INTO release_search_data (releaseid, guid, name, searchname, fromname) VALUES (NEW.id, NEW.guid, NEW.name, NEW.searchname, NEW.fromname); END;
CREATE TRIGGER update_search AFTER UPDATE ON releases FOR EACH ROW BEGIN IF NEW.guid != OLD.guid THEN UPDATE release_search_data SET guid = NEW.guid WHERE releaseid = OLD.id; END IF; IF NEW.name != OLD.name THEN UPDATE release_search_data SET name = NEW.name WHERE releaseid = OLD.id; END IF; IF NEW.searchname != OLD.searchname THEN UPDATE release_search_data SET searchname = NEW.searchname WHERE releaseid = OLD.id; END IF; IF NEW.fromname != OLD.fromname THEN UPDATE release_search_data SET fromname = NEW.fromname WHERE releaseid = OLD.id; END IF; END;
CREATE TRIGGER delete_search AFTER DELETE ON releases FOR EACH ROW BEGIN DELETE FROM release_search_data WHERE releaseid = OLD.id; END;
