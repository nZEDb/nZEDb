# This patch will make the necessary changes to the user_series
# table to support the new videos implementation
# At this time, we're not sure if we can preserve the users shows
# data as the tvrage data is largely useless due to the title insert bug

# Truncate for now
TRUNCATE TABLE user_series;

# Change rageid column to new videos_id
ALTER TABLE user_series
  DROP INDEX ix_userseries_userid,
  CHANGE COLUMN rageid videos_id INT(16) NOT NULL COMMENT 'FK to videos.id',
  ADD INDEX ix_userseries_videos_id (user_id, videos_id);