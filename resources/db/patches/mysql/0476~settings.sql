# Add setting to check when was the last time we updated steam_apps table

INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting) VALUES ('APIs', 'Steam', 'last_update', '0', 'Last time we updated steam_apps table.', 'laststeamupdate');
