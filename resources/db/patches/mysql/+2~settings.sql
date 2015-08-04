INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting)
VALUES (
  'indexer',
  'processing',
  'collection_timeout',
  '2',
  'How many days to wait before converting a collection into a release that is considered "stuck".',
  'collection_timeout'
);
