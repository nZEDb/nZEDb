# This patch creates the new tv_episodes table
# It will not fill it with data at this time

DROP TABLE IF EXISTS tv_episodes;
CREATE TABLE tv_episodes (
  video_id    MEDIUMINT(11) UNSIGNED  NOT NULL COMMENT 'FK to videos.id of the parent series.',
  series      SMALLINT(5) UNSIGNED    NOT NULL DEFAULT '0' COMMENT 'Number of series/season.',
  episode     SMALLINT(5) UNSIGNED    NOT NULL DEFAULT '0' COMMENT 'Number of episode within series',
  se_complete VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL COMMENT 'String version of Series/Episode as taken from release subject (i.e. S02E21+22).',
  title       VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Title of the episode.',
  firstaired  DATE NOT NULL COMMENT 'Date of original airing/release.',
  summary     VARCHAR(1000) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Description/summary of the episode.',
  PRIMARY KEY (video_id, se_complete, firstaired)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;