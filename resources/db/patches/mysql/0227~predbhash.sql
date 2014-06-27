DROP TRIGGER IF EXISTS update_hashes;
CREATE TRIGGER update_hashes AFTER UPDATE ON predb FOR EACH ROW BEGIN IF NEW.title != OLD.title THEN UPDATE predbhash SET hashes = CONCAT_WS(',', MD5(NEW.title), MD5(MD5(NEW.title)), SHA1(NEW.title)) WHERE pre_id = OLD.id; END IF; END;
