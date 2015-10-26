# This patch creates the new tv_info table
# It will not fill it with data at this time

DROP TABLE IF EXISTS tv_info;
CREATE TABLE tv_info (
  videos_id MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'FK to video.id',
  summary   TEXT          CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Description/summary of the show.',
  publisher VARCHAR(50)   CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The channel/network of production/release (ABC, BBC, Showtime, etc.).',
  image     TINYINT(1)    UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Does the video have a cover image?',
  PRIMARY KEY          (videos_id),
  KEY ix_tv_info_image (image)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
