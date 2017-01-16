# Create the new multigroup_posters TABLE

DROP TABLE IF EXISTS multigroup_posters;
CREATE TABLE multigroup_posters (
  id           INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  poster       VARCHAR(255)        NOT NULL DEFAULT '' COMMENT 'Name of the poster to track',
  PRIMARY KEY (id) ,
  UNIQUE KEY (poster)
)
ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT = 1;
