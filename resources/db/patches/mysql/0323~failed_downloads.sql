DROP TABLE IF EXISTS dnzb_failures;
CREATE TABLE dnzb_failures (
  id         INT(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
  userid     INT(11) UNSIGNED        NOT NULL,
  guid       VARCHAR(50)             NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX ux_dnzb_failures (userid, guid)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 1;