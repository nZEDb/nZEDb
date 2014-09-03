DROP TABLE IF EXISTS animetitles;
DROP TABLE IF EXISTS anidb;

CREATE TABLE anidb (
  id INT(10) UNSIGNED NOT NULL COMMENT 'ID from anidb site, also the ID other tables use as FK',
  type VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL COMMENT 'type of title.',
  lang VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL,
  title VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (anidb_id, type, lang, title)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
