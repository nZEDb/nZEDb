DROP TABLE IF EXISTS anidb_episodes;

CREATE TABLE anidb_episodes (
  anidb_id INT(10) UNSIGNED NOT NULL COMMENT 'FK for main title',
  episode_id INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'anidb id for this episode',
  episode_no SMALLINT(5) UNSIGNED NOT NULL COMMENT 'Numeric version of episode (leave 0 for combined episodes).',
  episode_title VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Title of the episode (en, x-jat)',
  airdate date NOT NULL DEFAULT '',
  PRIMARY KEY (anidb_id, episode_id)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
