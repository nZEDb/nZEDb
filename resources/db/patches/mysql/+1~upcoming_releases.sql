DROP TABLE IF EXISTS upcoming_releases;
CREATE TABLE upcoming_releases (
  id           INT(10)                               NOT NULL AUTO_INCREMENT,
  source       VARCHAR(20)                           NOT NULL,
  typeid       INT(10)                               NOT NULL,
  relid        INT(10)                               NOT NULL,
  popularity   FLOAT                                 NOT NULL,
  release_date TIMESTAMP                             NOT NULL,
  info         LONGTEXT                              NULL,
  updateddate  TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE INDEX ix_upcoming_source (source, relid)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;