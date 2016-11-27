# Make sure 'indexer', 'ppa', 'innerfileblacklist' exists in Settings table.
REPLACE INTO settings (section, subsection, name, value, hint, setting) VALUES ('indexer', 'ppa',
'innerfileblacklist', '/setup\.exe|password\.url/i', 'You can add a regex here to set releases to potentially passworded when a file name inside a rar/zip matches this regex.', 'innerfileblacklist');
