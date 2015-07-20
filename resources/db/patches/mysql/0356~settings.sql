REPLACE INTO settings (section, subsection, name, value, hint, setting)
VALUES (
  'max',
  'headers',
  'iteration',
  1000000,
  'The maximum number of headers that update binaries is given as the total range. This ensures that a total of no more than this, is downloaded per group.',
  'max_headers_iteration'
);
