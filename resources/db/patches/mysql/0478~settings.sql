# Add setting to check when was the last time we updated steam_apps table

UPDATE settings SET subsection = 'steam_apps', setting = 'laststeamappsupdate' WHERE section = 'APIs' AND subsection = 'Steam';
