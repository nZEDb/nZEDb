# This patch creates the videos_akas table to store AKAs retrieved
# from scrape sources.  This will keep a record of them for local
# lookup searches to speed up processing and prevent API hammering

DROP TABLE IF EXISTS videos_aliases;
CREATE TABLE videos_aliases (
  videos_id   MEDIUMINT(11) UNSIGNED  NOT NULL COMMENT 'FK to videos.id of the parent title.',
  title VARCHAR(180) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AKA of the video.',
  PRIMARY KEY (videos_id, title)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT  = 100000;