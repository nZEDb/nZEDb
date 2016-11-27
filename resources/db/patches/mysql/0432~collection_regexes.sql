#Replacing collection regex
REPLACE INTO collection_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  391,
  '^alt\.binaries\.mom$',
  '/^(?P<match0>\\d+)\\-\\d+\\s?\\[\\d+\\/\\d+\\] - ".+?" yEnc$/',
  1,
  '//58600-0[51/51] - "58600-0.vol0+1.par2" yEnc',
  35
);
