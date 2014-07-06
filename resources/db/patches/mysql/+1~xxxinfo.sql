DROP TABLE IF EXISTS xxxinfo;
CREATE TABLE         xxxinfo (
  id          INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255)        NOT NULL,
  tagline        VARCHAR(128)        DEFAULT NULL,
  plot         VARCHAR(1000)       DEFAULT NULL,
  genre    VARCHAR(255)        DEFAULT NULL,
  director   VARCHAR(255)        DEFAULT NULL,
  actors     INT(10)             NULL DEFAULT NULL,
  esrb        VARCHAR(255)        NULL DEFAULT NULL,
  releasedate DATETIME            DEFAULT NULL,
  review      VARCHAR(3000)       DEFAULT NULL,
  cover       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  createddate DATETIME            NOT NULL,
  updateddate DATETIME            NOT NULL,
  PRIMARY KEY                    (id),
  UNIQUE INDEX ix_gamesinfo_asin (asin)
)
  ENGINE          = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 1;
