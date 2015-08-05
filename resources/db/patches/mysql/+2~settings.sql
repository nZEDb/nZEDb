INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting)
VALUES (
  'indexer',
  'processing',
  'collection_timeout',
  '48',
  'How many hours to wait before deleting a stuck/broken collection. (This is to prevent the MySQL tables from swelling up.)',
  'collection_timeout'
), (
  'indexer',
  'processing',
  'last_run_time',
  '3015-08-04 15:58:23',
  'Last date the indexer (update_binaries or backfill) was run.',
  'last_run_time'
);
