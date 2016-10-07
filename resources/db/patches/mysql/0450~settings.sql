# Add missing setting for settings table.
INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting) VALUES ('APIs', 'AniDB', 'banned', '0', "Timestamp of WHEN we were 'banned'.", 'AniDB_banned');
