# Create mgr tables

DROP TABLE IF EXISTS multigroup_binaries;
CREATE TABLE multigroup_binaries LIKE binaries;

DROP TABLE IF EXISTS multigroup_collections;
CREATE TABLE         multigroup_collections (
  id             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  subject        VARCHAR(255)        NOT NULL DEFAULT '' COMMENT 'Collection subject',
  fromname       VARCHAR(255)        NOT NULL DEFAULT '' COMMENT 'Collection poster',
  date           DATETIME            DEFAULT NULL COMMENT 'Collection post date',
  xref           VARCHAR(510)        NOT NULL DEFAULT '' COMMENT 'Groups collection is posted in',
  totalfiles     INT(11) UNSIGNED    NOT NULL DEFAULT '0' COMMENT 'Total number of files',
  groups_id      INT(11) UNSIGNED    NOT NULL DEFAULT '0' COMMENT 'FK to groups.id',
  collectionhash VARCHAR(255)        NOT NULL DEFAULT '0' COMMENT 'MD5 hash of the collection',
  dateadded      DATETIME            DEFAULT NULL COMMENT 'Date collection is added',
  added          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  filecheck      TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Status of the collection',
  filesize       BIGINT UNSIGNED     NOT NULL DEFAULT '0' COMMENT 'Total calculated size of the collection',
  releases_id    INT                 NULL COMMENT 'FK to releases.id',
  noise          CHAR(32)            NOT NULL DEFAULT '',
  PRIMARY KEY                               (id),
  INDEX        fromname                     (fromname),
  INDEX        date                         (date),
  INDEX        group_id                     (groups_id),
  INDEX        ix_collection_filecheck      (filecheck),
  INDEX        ix_collection_dateadded      (dateadded),
  INDEX        ix_collection_releaseid      (releases_id),
  UNIQUE INDEX ix_collection_collectionhash (collectionhash)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 1;

DROP TABLE IF EXISTS multigroup_parts;
CREATE TABLE multigroup_parts LIKE parts;

DROP TABLE IF EXISTS multigroup_missed_parts;
CREATE TABLE multigroup_missed_parts LIKE missed_parts;
