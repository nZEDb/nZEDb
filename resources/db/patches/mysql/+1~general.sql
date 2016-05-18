# Change predb.group_id to predb.groups_id to follow lithium convention.
ALTER TABLE predb
  CHANGE COLUMN group_id groups_id INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to groups.id';

# Change predb_imports.group_id to predb_imports.groups_id to follow lithium convention.
ALTER TABLE predb_imports
  CHANGE COLUMN group_id groups_id INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to groups.id';

# Change releases.group_id to releases.groups_id to follow lithium convention.
ALTER TABLE releases
  CHANGE COLUMN group_id groups_id INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to groups.id';
