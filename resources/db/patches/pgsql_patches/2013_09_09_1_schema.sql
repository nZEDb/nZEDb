UPDATE releases SET reqidstatus = -1 WHERE reqidstatus = 0 AND nzbstatus = 1 AND relnamestatus IN (0, 1) AND name ~'^\\[[[:digit:]]+\\]';

DROP FUNCTION IF EXISTS request_check() CASCADE;
DROP TRIGGER IF EXISTS request_check ON releases;

CREATE FUNCTION request_check() RETURNS trigger AS $request_check$ BEGIN IF NEW.searchname ~'^\\[[[:digit:]]+\\]' OR NEW.name ~'^\\[[[:digit:]]+\\]' THEN SET NEW.reqidstatus = -1; END IF; END; $request_check$ LANGUAGE plpgsql;
CREATE TRIGGER request_check BEFORE INSERT OR UPDATE ON releases FOR EACH ROW EXECUTE PROCEDURE request_check();

UPDATE site SET value = '121' WHERE setting = 'sqlpatch';
