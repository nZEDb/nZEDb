DROP TABLE IF EXISTS anidb;
DROP TABLE IF EXISTS animetitles;
DROP TABLE IF EXISTS anime_titles;
DROP TABLE IF EXISTS anidb_titles;

CREATE TABLE anidb_titles (
  anidbid INT(10) UNSIGNED NOT NULL COMMENT 'ID of title from AniDB',
  type VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL COMMENT 'type of title.',
  lang VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL,
  title VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (anidbid, type, lang, title)
) ENGINE = MYISAM
 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
