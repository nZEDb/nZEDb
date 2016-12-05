#Update AniDb settings that may have missed being chenged by SQL patch 292.
UPDATE IGNORE settings SET section = 'APIs', subsection = 'anidb', name = 'max_update_frequency' WHERE name = 'lastanidbupdate';
UPDATE IGNORE settings SET section = 'APIs', subsection = 'anidb', name = 'last_full_update' WHERE name = 'intanidbupdate';
