ALTER TABLE videos
  CHANGE COLUMN countries_id country_id CHAR(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
  COMMENT 'Two character country code (FK to countries table).';
