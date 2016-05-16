# Rename pre_id to predb_id in line with lithium conventions.
ALTER TABLE predb_hashes
CHANGE COLUMN pre_id predb_id INT(11) UNSIGNED NOT NULL
COMMENT 'id, of the predb entry, this hash belongs to';
