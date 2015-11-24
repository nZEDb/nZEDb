DELETE FROM collection_regexes WHERE id = 600;
INSERT INTO collection_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  600,
  '^alt\\.binaries\\.cores$',
  '/^(?P<match0>.+?) - \\[\\d+\\/\\d+\\] - ".+?" yEnc$/',
  1,
  'Gracepoint S01E04 Episode Four 1080p WEB-DL DD51 H 264 CtrlHD - [52/52] - "Gracepoint S01E04 Episode Four 1080p WEB-DL DD51 H 264 CtrlHD.vol511+021.par2" yEnc',
  80
);