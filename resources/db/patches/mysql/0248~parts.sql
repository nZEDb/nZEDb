ALTER TABLE parts DROP INDEX ix_parts_messageid;
ALTER TABLE parts DROP INDEX ix_parts_number;
ALTER TABLE parts ADD COLUMN collection_id INT(11) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE parts ADD INDEX ix_parts_collection_id(collection_id);
DROP TRIGGER IF EXISTS delete_collections;
CREATE TRIGGER delete_collections AFTER DELETE ON collections FOR EACH ROW BEGIN DELETE FROM binaries WHERE collectionid = OLD.id; DELETE FROM parts WHERE collection_id = OLD.id; END;