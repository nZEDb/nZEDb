DROP TABLE IF EXISTS animetitles;
DROP TABLE IF EXISTS anidb;

CREATE TABLE anidb_titles (
  anidbid INT(10) UNSIGNED NOT NULL COMMENT 'Main ID from AniDB for title',
  type VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL COMMENT 'type of title.',
  lang VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL,
  title VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (anidbid, type, lang, title)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
