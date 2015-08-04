INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting)
VALUES (
  'indexer',
  'processing',
  'collection_timeout',
  '48',
  'How many hours to wait before converting a collection into a release that is considered "stuck".',
  'collection_timeout'
), (
  'indexer',
  'processing',
  'last_run_time',
  '3015-08-04 15:58:23',
  'Last date the indexer (update_binaries or backfill) was run.',
  'last_run_time'
);
