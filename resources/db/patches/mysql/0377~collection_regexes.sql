DELETE FROM collection_regexes WHERE id = 413;
INSERT INTO collection_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  413,
  '^alt\\.binaries\\.moovee$',
  '/^\\[\\d+\\]-\\[\\d+(?P<match0>\\/\\d+\\] - ".+?)([-_](proof|sample|subs|thumbs?))*(\\.part\\d*(\\.rar)?|\\.rar|\\.7z)?(\\d{1,3}\\.rev"|\\.vol\\d+\\+\\d+\\.par2"|\\.[A-Za-z0-9]{2,4}"|")[-_\\s]{0,3}yEnc$/ui',
  1,
  '//[134517]-[01/76] - "Lara Croft Tomb Raider 2001 720p BluRay DTS x264-RightSiZE.nfo" yEnc',
  45
);