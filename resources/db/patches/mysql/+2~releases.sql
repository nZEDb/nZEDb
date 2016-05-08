ALTER TABLE releases
  CHANGE COLUMN preid predb_id INT(11) UNSIGNED NOT NULL COMMENT 'id, of the predb entry, this hash belongs to',
  CHANGE COLUMN musicinfoid musicinfo_id INT(11) UNSIGNED NULL COMMENT 'FK to musicinfo.id',
  CHANGE COLUMN consoleinfoid consoleinfo_id INT(11) UNSIGNED NULL COMMENT 'FK to consoleinfo.id',
  CHANGE COLUMN bookinfoid bookinfo_id INT(11) UNSIGNED NULL COMMENT 'FK to bookinfo.id';
