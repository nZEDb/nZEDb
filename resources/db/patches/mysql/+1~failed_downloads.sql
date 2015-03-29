DROP TABLE IF EXISTS failed_downloads;
CREATE TABLE failed_downloads (
  id          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  guid        VARCHAR(50)      NOT NULL,
  userid      INT(11) UNSIGNED NOT NULL,
  status      TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
)
  ENGINE =MYISAM
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci
  AUTO_INCREMENT =1;
  CREATE INDEX ix_failed_downloads_guid ON failed_downloads (guid);
  CREATE INDEX ix_failed_downloads_userid ON failed_downloads (userid);
  CREATE UNIQUE INDEX ux_index_failed ON failed_downloads (guid, userid);