ALTER TABLE collection_regexes MODIFY COLUMN description VARCHAR(1000) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;

INSERT INTO collection_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  592,
  '^alt\\.binaries\\.multimedia\\.korean$',
  '/^(?P<match0>\\[KoreanTV\\]\s.+?\\[)\\d+\\/\\d+\\] - ".+?" yEnc$/',
  1,
  '[KoreanTV] The.Village.Achiaras.Secret.E08.151029.HDTV.XviD-WITH [2/18] - "마을.아치아라의.비밀.E08.151029.HDTV.XviD-WITH.part01.rar" yEnc',
  5
), (
  593,
  '^korea\\.binaries\\.tv$',
  '/^(?P<match0>\\[KoreanTV\\]\s.+?\\[)\\d+\\/\\d+\\] - ".+?" yEnc$/',
  1,
  '[KoreanTV] The.Village.Achiaras.Secret.E08.151029.HDTV.XviD-WITH [2/18] - "마을.아치아라의.비밀.E08.151029.HDTV.XviD-WITH.part01.rar" yEnc',
  5
), (
  594,
  '^korea\\.binaries\\.music\\.videos$',
  '/^(?P<match0>\\[KoreanMusic\\] .+) \\[\\d+\\/\\d+\\] - ".+?" yEnc$/',
  1,
  '[KoreanMusic] 151015.TWTV.MTV.Idols.of.Asia.Lovelyz [2/45] - "151015.TWTV.MTV.Idols.of.Asia.Lovelyz.part01.rar" yEnc',
  5
), (
  595,
  '^korea\\.binaries\\.movies$',
  '/^(?P<match0>\\[KoreanMovies\\] .+) \\[\\d+\\/\\d+\\] - ".+?" yEnc$/',
  1,
  '[KoreanMovies] Gangnam.Blues.2015.720p.BluRay.x264-WiKi [2/125] - "Gangnam.Blues.2015.720p.BluRay.x264-WiKi.part001.rar" yEnc',
  5
), (
  596,
  '^korea\\.binaries\\.tv$',
  '/^(?P<match0>area-11) "(?P<match1>.+?)\\.(vol\\d+\\+\\d+\\.par2|part\\d+\\.rar|par2|nzb)" \\[\\d+\\/\\d+\\] yEnc$/i',
  1,
  'area-11 "Glamorous.Temptation.E01.720p.HDTV.x264-AREA11.nzb" [00/56] yEnc',
  10
), (
  597,
  '^alt\\.binaries\\.multimedia\\.korean$',
  '/^(?P<match0>.+?(MP3 320K|FLAC)\\[)\\d+\\/\\d+\\] - ".+" yEnc$/',
  1,
  'Davichi - Amaranth Repackage MP3 320K[01/21] - ".par2" yEnc',
  10
);
