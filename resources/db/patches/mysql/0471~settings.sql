# Fix bad patch 456, which had the conditions reversed screwing up two anidb settings.
UPDATE IGNORE settings SET section = 'APIs', subsection = 'anidb', name = 'temp' WHERE name = 'lastanidbupdate';
UPDATE IGNORE settings SET section = 'APIs', subsection = 'anidb', name = 'max_update_frequency' WHERE name = 'intanidbupdate';
UPDATE IGNORE settings SET section = 'APIs', subsection = 'anidb', name = 'last_full_update' WHERE name = 'lastanidbupdate';
