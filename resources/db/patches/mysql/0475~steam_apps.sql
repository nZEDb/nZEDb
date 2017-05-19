# Create the new steam_apps table

DROP TABLE IF EXISTS steam_apps;
CREATE TABLE steam_apps (
  name         VARCHAR(255)        NOT NULL DEFAULT '' COMMENT 'Steam application name',
  appid        INT(11) UNSIGNED    NULL COMMENT 'Steam application id',
  PRIMARY KEY (appid, name),
  FULLTEXT INDEX ix_name_ft (name)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
