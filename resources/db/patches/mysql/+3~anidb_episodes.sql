DROP TABLE IF EXISTS anidb_episodes;

CREATE TABLE anidb_episodes (
  anidbid INT(10) UNSIGNED NOT NULL COMMENT 'ID of title from AniDB',
  episodeid INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'anidb id for this episode',
  episode_no SMALLINT(5) UNSIGNED NOT NULL COMMENT 'Numeric version of episode (leave 0 for combined episodes).',
  episode_title VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Title of the episode (en, x-jat)',
  airdate date NOT NULL DEFAULT '',
  PRIMARY KEY (anidbid, episode_id)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
