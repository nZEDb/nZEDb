INSERT IGNORE INTO tmux (setting, value) VALUE ('SHOWQUERY', 'FALSE');

DROP TABLE IF EXISTS allgroups;
CREATE TABLE allgroups (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL DEFAULT "",
  first_record BIGINT UNSIGNED NOT NULL DEFAULT "0",
  last_record BIGINT UNSIGNED NOT NULL DEFAULT "0",
  updated DATETIME DEFAULT NULL,
  PRIMARY KEY  (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE INDEX ix_allgroups_id ON allgroups(id);
CREATE INDEX ix_allgroups_name ON allgroups(name);

UPDATE site SET value = '126' WHERE setting = 'sqlpatch';
