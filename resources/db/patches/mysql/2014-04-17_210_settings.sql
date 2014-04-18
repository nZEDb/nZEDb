CREATE TABLE settings (
  section VARCHAR(25)  NOT NULL DEFAULT '',
  subsection VARCHAR(25)  NOT NULL DEFAULT '',
  name    VARCHAR(25)  NOT NULL DEFAULT '',
  value   VARCHAR(255) NOT NULL DEFAULT '',
  hint    VARCHAR(19000) NOT NULL DEFAULT '',
  setting VARCHAR(64) NOT NULL DEFAULT '',
  PRIMARY KEY (section, subsection, name)
) ENGINE=MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE UNIQUE INDEX ui_settings_setting ON settings (setting);

LOAD DATA {:local:}INFILE '{:data:}10-settings.tsv' IGNORE INTO TABLE settings FIELDS TERMINATED BY '\t' OPTIONALLY  ENCLOSED BY '"' ESCAPED BY '\\' LINES TERMINATED BY '\n' IGNORE 1 LINES (section, subsection, name, value, hint, setting);

UPDATE settings RIGHT JOIN site ON settings.setting = site.setting SET settings.value = site.value WHERE site.value != settings.value;

DROP TABLE IF EXISTS site;
