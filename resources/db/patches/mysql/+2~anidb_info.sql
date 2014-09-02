DROP TABLE IF EXISTS anidb_info;

CREATE TABLE anidb_info (
  anidb_id INT(7) UNSIGNED NOT NULL,
  type VARCHAR(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  startdate DATE DEFAULT NULL,
  enddate DATE DEFAULT NULL,
  related VARCHAR(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  creators VARCHAR(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  description text COLLATE utf8_unicode_ci DEFAULT NULL,
  rating VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  picture VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  categories VARCHAR(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  characters VARCHAR(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  updatetime DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (anidb_id)
  KEY ix_anidb_info_datetime (startdate, enddate, updatetime);
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;