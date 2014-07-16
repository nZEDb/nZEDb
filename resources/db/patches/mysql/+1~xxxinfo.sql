DROP TABLE IF EXISTS xxxinfo;
CREATE TABLE         xxxinfo (
  id          INT(10) UNSIGNED               NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255)                   NOT NULL,
  tagline     VARCHAR(1024)                  NOT NULL,
  plot        VARCHAR(1024)                  NOT NULL,
  genre       VARCHAR(64)                    NOT NULL,
  director    VARCHAR(64)                    DEFAULT NULL,
  actors      VARCHAR(2000)                  NOT NULL,
  extras      TEXT                           DEFAULT NULL,
  productinfo TEXT                           DEFAULT NULL,
  trailers    TEXT                           DEFAULT NULL,
  directurl   VARCHAR(2000)                  NOT NULL,
  classused   VARCHAR(3)                     NOT NULL,
  cover       TINYINT(1) UNSIGNED            NOT NULL DEFAULT '0',
  backdrop    TINYINT(1) UNSIGNED            NOT NULL DEFAULT '0',
  createddate DATETIME                       NOT NULL,
  updateddate DATETIME                       NOT NULL,
  PRIMARY KEY                      (id),
  INDEX        ix_xxxinfo_title  (title)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 1;
