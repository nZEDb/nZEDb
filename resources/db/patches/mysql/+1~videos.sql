# This patch creates the new videos table
# It will not fill it with data at this time

DROP TABLE IF EXISTS videos;
CREATE TABLE videos (
  id           MEDIUMINT(11)  UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Show''s ID to be used in other tables as reference.',
  type         TINYINT(1)     UNSIGNED NOT NULL DEFAULT '0' COMMENT '0 = TV, 1 = Film, 2 = Anime',
  title        VARCHAR(180)   CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of the video.',
  countries_id CHAR(2)                        COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Two character country code (FK to countries table).',
  started      DATETIME               NOT NULL COMMENT 'Date (UTC) of production''s first airing.',
  imdb         MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for IMDB site (without the ''tt'' prefix).',
  tmdb         MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for TMDB site.',
  trakt        MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for TraktTV site.',
  tvdb         MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for TVDB site',
  tvmaze       MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for TVMaze site.',
  tvrage       MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for TVRage site.',
  source       TINYINT(1)    UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Which site did we use for info?',
  PRIMARY KEY (id),
  UNIQUE KEY  ix_videos_title (title, type, started, countries_id),
  KEY         ix_videos_imdb (imdb),
  KEY         ix_videos_tmdb (tmdb),
  KEY         ix_videos_trakt (trakt),
  KEY         ix_videos_tvdb (tvdb),
  KEY         ix_videos_tvmaze (tvmaze),
  KEY         ix_videos_tvrage (tvrage),
  KEY         ix_videos_type_source (type, source)
)
  ENGINE = MyISAM
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
