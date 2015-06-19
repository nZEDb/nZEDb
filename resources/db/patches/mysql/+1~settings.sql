INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting)
VALUES (
  'APIs',
  '',
  'trakttvclientkey',
  '',
  'The Trakt.tv API v2 Client ID (SHA256 hash - 64 characters long string). Used for movie and tv lookups.',
  'trakttvclientkey'
);
