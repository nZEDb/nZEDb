DROP TABLE IF EXISTS anidb_info;

CREATE TABLE anidb_info (
  anidbid INT(10) UNSIGNED NOT NULL COMMENT 'ID of title from AniDB',
  type VARCHAR(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  startdate DATE DEFAULT NULL,
  enddate DATE DEFAULT NULL,
  updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  related VARCHAR(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  creators VARCHAR(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  description TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
  rating VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  picture VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  categories VARCHAR(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  characters VARCHAR(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (anidbid),
  KEY ix_anidb_info_datetime (startdate, enddate, updated)
) ENGINE = MYISAM
 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
