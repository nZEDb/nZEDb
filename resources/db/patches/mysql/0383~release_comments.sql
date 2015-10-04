-- This patch will add a text_hash column and triggers
-- to prevent insertion of duplicated automated comments and sharing sources

ALTER TABLE release_comments ADD COLUMN text_hash VARCHAR(32) NOT NULL DEFAULT '';

DELIMITER $$

DROP TRIGGER IF EXISTS insert_MD5 $$
CREATE TRIGGER insert_MD5 BEFORE INSERT ON release_comments FOR EACH ROW SET NEW.text_hash = MD5(NEW.text); $$

DELIMITER ;

UPDATE release_comments SET text_hash = MD5(text);
ALTER IGNORE TABLE release_comments ADD UNIQUE INDEX ix_release_comments_hash_releaseid(text_hash, releaseid);