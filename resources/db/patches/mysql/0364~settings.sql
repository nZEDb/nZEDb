-- Adds the default settings for movie trailers.
INSERT IGNORE INTO settings (section, subsection, name, value, hint, setting)
VALUES (
  'site',
  'trailers',
  'trailers_display',
  '1',
  'Display trailers on the details page?',
  'trailers_display'
), (
  'site',
  'trailers',
  'trailers_size_x',
  '480',
  'Width of the displayed trailer. 480 by default.',
  'trailers_size_x'
), (
  'site',
  'trailers',
  'trailers_size_y',
  '345',
  'Height of the displayed trailer. 345 by default.',
  'trailers_size_y'
);
