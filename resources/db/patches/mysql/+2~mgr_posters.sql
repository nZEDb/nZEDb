#Create the new mgr_posters TABLE

DROP TABLE IF EXISTS mgr_posters;
CREATE TABLE mgr_posters (
  id             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  poster       VARCHAR(255)        NOT NULL DEFAULT '',
  PRIMARY KEY (id) ,
  UNIQUE KEY (poster)
)
ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT = 1;
