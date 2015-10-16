# This patch will create the videos_id column
# It will also update this column to reflect the new videos_id
# It will then create the tv_episodes_id column

# First add the new columns
ALTER TABLE releases
	DROP INDEX ix_releases_rageid,
	ADD COLUMN videos_id MEDIUMINT(11) UNSIGNED NOT NULL COMMENT 'FK to videos.id of the parent series.' AFTER rageid,
	ADD COLUMN tv_episodes_id MEDIUMINT(11) UNSIGNED NOT NULL COMMENT 'FK to tv_episodes.id of the episode' AFTER videos_id;

# Now update the videos_id column with the new videos ID
UPDATE releases r INNER JOIN videos_id v ON r.rageid = v.tvrage SET r.videos_id = v.id WHERE r.rageid > 0 AND r.categoryid BETWEEN 5000 AND 5999;

# Now update the episode ID column with the new episode ID
UPDATE releases r INNER JOIN tv_episodes tve ON r.videos_id = tve.videos_id AND r.seriesfull = tve.se_complete SET r.tv_episodes_id = tve.id WHERE r.categoryid BETWEEN 5000 AND 5999;

# Reset all -1,-2 TV shows to undergo new processing
UPDATE releases r SET videos_id = 0 WHERE videos_id < 0;

# Drop the old columns and indexes we no longer need from releases
ALTER TABLE releases
	DROP COLUMN rageid,
	DROP COLUMN season,
	DROP COLUMN episode,
	DROP COLUMN seriesfull,
	DROP COLUMN tvtitle,
	DROP COLUMN tvairdate
	DROP INDEX ix_releases_rageid;

# Analyze to refresh our indexes
ANALYZE TABLE releases;