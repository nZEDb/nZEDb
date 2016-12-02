# Add new table for handling mulitple group per release support.
CREATE TABLE releases_groups (
  releases_id INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to releases.id',
  groups_id   INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to groups.id',
  PRIMARY KEY (releases_id, groups_id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
