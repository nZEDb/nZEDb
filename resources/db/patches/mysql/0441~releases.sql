# Fix Search insert triggers after releases_id change

DELIMITER $$

DROP TRIGGER IF EXISTS insert_search;
CREATE TRIGGER insert_search AFTER INSERT ON releases FOR EACH ROW
  BEGIN
    INSERT INTO release_search_data (releases_id, guid, name, searchname, fromname) VALUES (NEW.id, NEW.guid, NEW.name, NEW.searchname, NEW.fromname);
  END; $$

DROP TRIGGER IF EXISTS update_search;
CREATE TRIGGER update_search AFTER UPDATE ON releases FOR EACH ROW
  BEGIN
    IF NEW.guid != OLD.guid
    THEN UPDATE release_search_data SET guid = NEW.guid WHERE releases_id = OLD.id;
    END IF;
    IF NEW.name != OLD.name
    THEN UPDATE release_search_data SET name = NEW.name WHERE releases_id = OLD.id;
    END IF;
    IF NEW.searchname != OLD.searchname
    THEN UPDATE release_search_data SET searchname = NEW.searchname WHERE releases_id = OLD.id;
    END IF;
    IF NEW.fromname != OLD.fromname
    THEN UPDATE release_search_data SET fromname = NEW.fromname WHERE releases_id = OLD.id;
    END IF;
  END; $$

DROP TRIGGER IF EXISTS delete_search;
CREATE TRIGGER delete_search AFTER DELETE ON releases FOR EACH ROW
  BEGIN
    DELETE FROM release_search_data WHERE releases_id = OLD.id;
  END; $$

DELIMITER ;