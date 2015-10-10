INSERT IGNORE INTO settings (name, value, hint, setting)
VALUES (
  'disablebackfillgroup', 0,
  'Whether to disable backfill on a group if the target date has been reached.',
  'disablebackfillgroup'
);