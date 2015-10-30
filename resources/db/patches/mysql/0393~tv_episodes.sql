# Drop old index to allow creating new one.
DROP INDEX ix_tv_episodes_videos_id ON tv_episodes;

# Change firstaired colum to be date only. date time was intended for videos table only
ALTER TABLE tv_episodes MODIFY firstaired DATE;

# Correct the index to include firstaired or by date episodes will only allow for the first as duplicate series = 0 and episode = 0 prevents all others
CREATE INDEX ux_videoid_series_episode_aired ON tv_episodes (videos_id, series, episode, firstaired);
