# This patch will create the videos_id column
# It will then create the tv_episodes_id column
# Then it will drop the old columns
# Followed by creating the new indexes
# And finally resetting TV releases to be processed again

# Add the new columns and drop the old old columns
# Then add the new indexes in one pass (saves time)

ALTER TABLE releases
	DROP INDEX ix_releases_rageid,
	ADD COLUMN videos_id MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to videos.id of the parent series.' AFTER categoryid,
	ADD COLUMN tv_episodes_id MEDIUMINT(11) SIGNED NOT NULL DEFAULT '0' COMMENT 'FK to tv_episodes.id of the episode' AFTER videos_id,
	DROP COLUMN rageid,
	DROP COLUMN season,
	DROP COLUMN episode,
	DROP COLUMN seriesfull,
	DROP COLUMN tvtitle,
	DROP COLUMN tvairdate,
	ADD INDEX ix_releases_videos_id (videos_id),
	ADD INDEX ix_releases_tv_episodes_id (tv_episodes_id);

# Analyze to refresh our indexes
ANALYZE TABLE releases;