-- This should be a quick patch.
-- You can verify them by running this command in MySQL: SHOW TRIGGERS LIKE 'predb' \g

DELIMITER $$

DROP TRIGGER IF EXISTS insert_hashes $$
CREATE TRIGGER insert_hashes AFTER INSERT ON predb FOR EACH ROW
  BEGIN
    INSERT INTO predb_hashes (hash, pre_id) VALUES (UNHEX(MD5(NEW.title)), NEW.id), (UNHEX(MD5(MD5(NEW.title))), NEW.id), ( UNHEX(SHA1(NEW.title)), NEW.id);
  END; $$

DROP TRIGGER IF EXISTS update_hashes $$
CREATE TRIGGER update_hashes AFTER UPDATE ON predb FOR EACH ROW
  BEGIN
    IF NEW.title != OLD.title
      THEN
         DELETE FROM predb_hashes WHERE hash IN ( UNHEX(md5(OLD.title)), UNHEX(md5(md5(OLD.title))), UNHEX(sha1(OLD.title)) ) AND pre_id = OLD.id;
         INSERT INTO predb_hashes (hash, pre_id) VALUES ( UNHEX(MD5(NEW.title)), NEW.id ), ( UNHEX(MD5(MD5(NEW.title))), NEW.id ), ( UNHEX(SHA1(NEW.title)), NEW.id );
    END IF;
  END; $$

DROP TRIGGER IF EXISTS delete_hashes $$
CREATE TRIGGER delete_hashes AFTER DELETE ON predb FOR EACH ROW
  BEGIN
    DELETE FROM predb_hashes WHERE hash IN ( UNHEX(md5(OLD.title)), UNHEX(md5(md5(OLD.title))), UNHEX(sha1(OLD.title)) ) AND pre_id = OLD.id;
  END; $$

DELIMITER ;
