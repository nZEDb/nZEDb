DROP TABLE IF EXISTS gamesinfo;
CREATE TABLE consoleinfo (
  id          INT(10) UNSIGNED        NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255)
              COLLATE utf8_unicode_ci NOT NULL,     
  asin        VARCHAR(128)
              COLLATE utf8_unicode_ci DEFAULT NULL, 
  url         VARCHAR(1000)
              COLLATE utf8_unicode_ci DEFAULT NULL,
  platform    VARCHAR(255)
              COLLATE utf8_unicode_ci DEFAULT NULL, 
  publisher   VARCHAR(255)
              COLLATE utf8_unicode_ci DEFAULT NULL, 
  genreid     INT(10)                 NULL DEFAULT NULL,
  esrb        VARCHAR(255)            NULL DEFAULT NULL,
  releasedate DATETIME DEFAULT NULL,
  review      VARCHAR(3000)
              COLLATE utf8_unicode_ci DEFAULT NULL,
  cover       TINYINT(1) UNSIGNED     NOT NULL DEFAULT '0',
  createddate DATETIME                NOT NULL,
  updateddate DATETIME                NOT NULL,
  PRIMARY KEY (id)
)
  ENGINE =MyISAM
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci
  AUTO_INCREMENT =1;

CREATE UNIQUE INDEX ix_gamesinfo_asin ON gamesinfo (asin);
