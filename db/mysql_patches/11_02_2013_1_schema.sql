INSERT IGNORE INTO site (setting, value) VALUE ('releasesthreads', '1');
INSERT IGNORE INTO tmux (setting, value) VALUE ('showquery', 'FALSE');

DROP INDEX ix_releases_mergedreleases ON releases;
DROP INDEX ix_releases_postdate ON releases;
DROP INDEX ix_releases_nzbstatus ON releases;

DROP TRIGGER IF EXISTS check_insert;
DROP TRIGGER IF EXISTS check_update;

ALTER TABLE releases ADD request BOOL DEFAULT false;

CREATE INDEX ix_releases_status ON releases (nzbstatus, id, nfostatus, relnamestatus, passwordstatus, dehashstatus, reqidstatus, musicinfoID, consoleinfoID, bookinfoID, haspreview, hashed, request, categoryid);
CREATE INDEX ix_releases_postdate ON releases (name, searchname, id, postdate);
CREATE INDEX ix_releases_postdate_searchname ON releases (postdate, searchname);
CREATE INDEX ix_releases_postdate_name ON releases (postdate, name);

CREATE TRIGGER check_insert BEFORE INSERT ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.hashed = true;ELSEIF NEW.name REGEXP '^\\[[[:digit:]]+\\]' THEN SET NEW.request = true; END IF; END;
CREATE TRIGGER check_update BEFORE UPDATE ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.hashed = true;ELSEIF NEW.name REGEXP '^\\[[[:digit:]]+\\]' THEN SET NEW.request = true; END IF; END;

UPDATE releases SET request = true WHERE name REGEXP '^\\[[[:digit:]]+\\]';

UPDATE site SET value = '135' WHERE setting = 'sqlpatch';
