# This patch creates the new videos table
# It will not fill it with data at this time

DROP TABLE IF EXISTS videos;
CREATE TABLE videos (
  id           MEDIUMINT(11) UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT 'Show''s ID to be used in other tables as reference.',
  type         TINYINT(1) UNSIGNED     NOT NULL DEFAULT '0' COMMENT '0 = TV, 1 = Film, 2 = Anime',
  title        VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Name of the video.',
  countries_id CHAR(2) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Two character country code (FK to countries table).',
  started      DATE                    NOT NULL COMMENT 'Date (UTC) of production''s first airing.',
  imdb         MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'ID number for IMDB site (without the ''tt'' prefix).',
  trakt        MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'ID number for TraktTV site.',
  tvdb         MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'ID number for TVDB site',
  tvmaze       MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'ID number for TVMaze site.',
  tvrage       MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'ID number for TVRage site.',
  source       TINYINT(1)    UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Which site did we use for info?',
  PRIMARY KEY (id),
  UNIQUE KEY title (title, type, started, countries_id),
  INDEX       ix_videos_tvrage (tvrage)
)
  ENGINE = MyISAM
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
