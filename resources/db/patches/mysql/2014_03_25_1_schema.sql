DROP TABLE IF EXISTS releasesearch;
CREATE TABLE releasesearch (
        id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        releaseid INT(11) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL DEFAULT '',
        searchname VARCHAR(255) NOT NULL DEFAULT '',
        PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

INSERT INTO releasesearch (releaseid, name, searchname) (SELECT id, name, searchname FROM releases);

CREATE FULLTEXT INDEX ix_releasesearch_name_searchname_ft ON releasesearch (name, searchname);

DROP TRIGGER IF EXISTS insert_search;
DROP TRIGGER IF EXISTS update_search;
DROP TRIGGER IF EXISTS delete_search;

CREATE TRIGGER insert_search AFTER INSERT ON releases FOR EACH ROW BEGIN INSERT INTO releasesearch (releaseid, name, searchname) VALUES (NEW.id, NEW.name, NEW.searchname); END;
CREATE TRIGGER update_search AFTER UPDATE ON releases FOR EACH ROW BEGIN IF NEW.name != OLD.name THEN UPDATE releasesearch SET name = NEW.name WHERE releaseid = OLD.id; END IF; IF NEW.searchname != OLD.searchname THEN UPDATE releasesearch SET searchname = NEW.searchname WHERE releaseid = OLD.id; END IF; END;
CREATE TRIGGER delete_search AFTER DELETE ON releases FOR EACH ROW BEGIN DELETE FROM releasesearch WHERE releaseid = OLD.id; END;

UPDATE site SET value = '187' WHERE setting = 'sqlpatch';
