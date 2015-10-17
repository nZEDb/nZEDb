# This patch will fix the tv_episodes_id column
ALTER TABLE releases MODIFY tv_episodes_id MEDIUMINT(11) SIGNED NOT NULL DEFAULT '0' COMMENT 'FK to tv_episodes.id of the episode'