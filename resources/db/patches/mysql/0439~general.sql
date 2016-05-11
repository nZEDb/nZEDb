# Change audio_data.releaseid to audio_data.releases_id to follow lithium convention.
ALTER TABLE audio_data CHANGE COLUMN releaseid releases_id INT(11) NOT NULL COMMENT 'FK to releases.id';

# Change release_comments.releaseid to release_comments.releases_id to follow lithium convention.
ALTER TABLE release_comments CHANGE COLUMN releaseid releases_id INT(11) NOT NULL COMMENT 'FK to releases.id';

# Change release_files.releaseid to release_files.releases_id to follow lithium convention.
ALTER TABLE release_files CHANGE COLUMN releaseid releases_id INT(11) NOT NULL COMMENT 'FK to releases.id';

# Change release_nfos.releaseid to release_nfos.releases_id to follow lithium convention.
ALTER TABLE release_nfos CHANGE COLUMN releaseid releases_id INT(11) NOT NULL COMMENT 'FK to releases.id';

# Change release_search_data.releaseid to release_search_data.releases_id to follow lithium convention.
ALTER TABLE release_search_data CHANGE COLUMN releaseid releases_id INT(11) NOT NULL COMMENT 'FK to releases.id';

# Change release_subtitles.releaseid to release_subtitles.releases_id to follow lithium convention.
ALTER TABLE release_subtitles CHANGE COLUMN releaseid releases_id INT(11) NOT NULL COMMENT 'FK to releases.id';

# Change releaseextrafull.releaseid to releaseextrafull.releases_id to follow lithium convention.
ALTER TABLE releaseextrafull CHANGE COLUMN releaseid releases_id INT(11) NOT NULL COMMENT 'FK to releases.id';

# Change users_releases.releaseid to users_releases.releases_id to follow lithium convention.
ALTER TABLE users_releases CHANGE COLUMN releaseid releases_id INT(11) NOT NULL COMMENT 'FK to releases.id';

# Change video_data.releaseid to video_data.releases_id to follow lithium convention.
ALTER TABLE video_data CHANGE COLUMN releaseid releases_id INT(11) NOT NULL COMMENT 'FK to releases.id';
