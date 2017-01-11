# Create mgr tables
DROP TABLE IF EXISTS multigroup_collections;
CREATE TABLE         multigroup_collections (
  id             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  subject        VARCHAR(255)        NOT NULL DEFAULT '',
  fromname       VARCHAR(255)        NOT NULL DEFAULT '',
  date           DATETIME            DEFAULT NULL,
  xref           VARCHAR(510)        NOT NULL DEFAULT '',
  totalfiles     INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  group_id       INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  collectionhash VARCHAR(255)        NOT NULL DEFAULT '0',
  dateadded      DATETIME            DEFAULT NULL,
  added          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  filecheck      TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  filesize       BIGINT UNSIGNED     NOT NULL DEFAULT '0',
  releaseid      INT                 NULL,
  noise          CHAR(32)            NOT NULL DEFAULT '',
  PRIMARY KEY                               (id),
  INDEX        fromname                     (fromname),
  INDEX        date                         (date),
  INDEX        group_id                     (group_id),
  INDEX        ix_collection_filecheck      (filecheck),
  INDEX        ix_collection_dateadded      (dateadded),
  INDEX        ix_collection_releaseid      (releaseid),
  UNIQUE INDEX ix_collection_collectionhash (collectionhash)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 1;

DROP TABLE IF EXISTS multigroup_binaries;
CREATE TABLE multigroup_binaries LIKE binaries;

# DROP TABLE IF EXISTS multigroup_parts;
CREATE TABLE multigroup_parts LIKE parts;

DROP TABLE IF EXISTS multigroup_missed_parts;
CREATE TABLE multigroup_missed_parts LIKE missed_parts;
