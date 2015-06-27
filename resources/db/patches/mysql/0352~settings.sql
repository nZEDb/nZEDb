INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting)
VALUES (
  'max',
  'headers',
  'iteration',
  1000000,
  'The maximum number of headers that update binaries sees as the total range. This ensure that a total of no mire than this is attempted to be downloaded at one time per group.',
  'max.headers.iteration'
);
