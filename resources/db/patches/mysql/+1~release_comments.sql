# Add text_hash column
ALTER TABLE release_comments ADD COLUMN text_hash VARCHAR(32) NOT NULL DEFAULT '';

#Drop the insert_md5 trigger, if it was present
DROP TRIGGER IF EXISTS insert_MD5;

#Add the trigger back
DELIMITER $$
CREATE TRIGGER insert_MD5 BEFORE INSERT ON release_comments FOR EACH ROW SET NEW.text_hash = MD5(NEW.text); $$
DELIMITER ;

#Update existing comments with new md5
UPDATE release_comments SET text_hash = MD5(text);

#Add unique index on text_hash, siteid and nzb_guid columns
ALTER IGNORE TABLE release_comments ADD UNIQUE INDEX ux_text_hash_siteid_nzb_guid (text_hash, siteid, nzb_guid);
