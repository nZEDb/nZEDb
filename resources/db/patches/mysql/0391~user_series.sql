# This patch will make the necessary changes to the user_series
# table to support the new videos implementation
# At this time, we're not sure if we can preserve the users shows
# data as the tvrage data is largely useless due to the title insert bug

# Drop the old user_series table
DROP TABLE IF EXISTS user_series;

# Create user_series table with new schema
CREATE TABLE user_series (
  id          INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT(16)          NOT NULL,
  videos_id   INT(16)          NOT NULL COMMENT 'FK to videos.id',
  categoryid  VARCHAR(64)      NULL DEFAULT NULL,
  createddate DATETIME         NOT NULL,
  PRIMARY KEY (id),
  INDEX ix_userseries_videos_id (user_id, videos_id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;