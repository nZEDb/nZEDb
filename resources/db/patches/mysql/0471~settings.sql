# Fix bad patch 456, which had the conditions reversed screwing up two anidb settings.
UPDATE IGNORE settings SET section = 'APIs', subsection = 'anidb', name = 'temp' WHERE setting = 'lastanidbupdate';
UPDATE IGNORE settings SET section = 'APIs', subsection = 'anidb', name = 'max_update_frequency' WHERE setting = 'intanidbupdate';
UPDATE IGNORE settings SET section = 'APIs', subsection = 'anidb', name = 'last_full_update' WHERE setting = 'lastanidbupdate';
