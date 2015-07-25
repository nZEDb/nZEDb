DELETE FROM release_naming_regexes
WHERE id = 218;
INSERT INTO release_naming_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  218,
  '^alt\\.binaries\\.comics\\.dcp$',
  '/.*"(?P<match0>.{7,}?)(\\.part\\d*|\\.rar)?(\\.[A-Za-z0-9]{2,4}").+?yEnc$/',
  1,
  '// Return anything between the quotes.',
  35
);