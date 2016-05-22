DROP TABLE IF EXISTS release_unique;
CREATE TABLE release_unique (
  releases_id   INT(11) UNSIGNED  NOT NULL COMMENT 'FK to releases.id.',
  uniqueid BINARY(16) NOT NULL COMMENT 'Unique_ID from mediainfo.',
  PRIMARY KEY (releases_id),
  KEY ix_release_unique_uniqueid(uniqueid)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
