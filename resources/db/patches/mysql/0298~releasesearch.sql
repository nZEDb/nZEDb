ALTER TABLE releasesearch ADD COLUMN fromname VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL;
UPDATE releasesearch rs INNER JOIN releases r ON rs.releaseid = r.id SET rs.fromname = r.fromname;

ALTER TABLE releasesearch DROP INDEX ix_releasesearch_name_searchname_ft;
ALTER TABLE releasesearch ADD FULLTEXT INDEX ix_releasesearch_name_ft (name);
ALTER TABLE releasesearch ADD FULLTEXT INDEX ix_releasesearch_searchname_ft (searchname);
ALTER TABLE releasesearch ADD FULLTEXT INDEX ix_releasesearch_fromname_ft (fromname);

DROP TRIGGER IF EXISTS insert_search;
DROP TRIGGER IF EXISTS update_search;

CREATE TRIGGER insert_search AFTER INSERT ON releases FOR EACH ROW BEGIN INSERT INTO releasesearch (releaseid, guid, name, searchname, fromname) VALUES (NEW.id, NEW.guid, NEW.name, NEW.searchname, NEW.fromname); END;
CREATE TRIGGER update_search AFTER UPDATE ON releases FOR EACH ROW BEGIN IF NEW.guid != OLD.guid THEN UPDATE releasesearch SET guid = NEW.guid WHERE releaseid = OLD.id; END IF; IF NEW.name != OLD.name THEN UPDATE releasesearch SET name = NEW.name WHERE releaseid = OLD.id; END IF; IF NEW.searchname != OLD.searchname THEN UPDATE releasesearch SET searchname = NEW.searchname WHERE releaseid = OLD.id; END IF; IF NEW.fromname != OLD.fromname THEN UPDATE releasesearch SET fromname = NEW.fromname WHERE releaseid = OLD.id; END IF; END;