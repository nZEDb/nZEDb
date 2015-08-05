ALTER TABLE parts DROP INDEX binaryid;
ALTER TABLE parts DROP INDEX ix_parts_collection_id;
ALTER TABLE parts ADD INDEX binaryid (binaryid,partnumber), ADD INDEX ix_parts_collection_id (collection_id,number), MODIFY messageid VARCHAR(255) CHARACTER SET latin1 NOT NULL DEFAULT '';
