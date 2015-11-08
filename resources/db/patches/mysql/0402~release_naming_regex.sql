DELETE FROM release_naming_regexes WHERE id in (1, 1130, 1155, 1156);
INSERT INTO release_naming_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  1,
  '^alt\\.binaries\\.teevee$',
  '/\\[\\d+\\]-\\[.+?\\]-\\[.+?\\]-\\[ (?P<match0>.+\\.S\\d\\dE\\d\\d\\..+?) \\][- ]\\[\\d+\\/\\d+\\][ -]{0,3}"[\\w\\säöüÄÖÜß+¤¶!.,&_()\\[\\]\\\'\\`{}-]{8,}?\\b.?([-_](proof|sample|thumbs?))*(\\.part\\d*(\\.rar)?|\\.rar|\\.7z)?(\\d{1,3}\\.rev"|\\.vol.+?"|\\.[A-Za-z0-9]{2,4}"|")[-_\\s]{0,3}\d+([.,]\\d+)? [kKmMgG][bB][-_\\s]{0,3}[-_\\s]{0,3}yEnc$/ui',
  1,
  'rename these teevee releases as the requestid is for the full season ::: [169018]-[FULL]-[a.b.teevee]-[ House.of.Lies.S01E01.720p.WEB-DL.DD5.1.H.264-BS ]-[04/32] - "House.of.Lies.S01E01.The.Gods.of.Dangerous.Financial.Instruments.720p.WEB-DL.DD5.1.H.264-BS.part03.rar" yEnc',
  5
), (
  1130,
  '^alt\\.binaries\\.teevee$',
  '#^\\[KoreanTV\\]\\s*(?P<match0>.+?)\\.[a-z0-9]+\\s+\\[\\d+/\\d+\\]\s+-\\s+".+?"\\s+yEnc$#i',
  1,
  '[KoreanTV] Star.King.E412.150509.HDTV.H264.720p-WITH.mp4 [1/36] - "ë†€ë�¼ìš´ ëŒ€íšŒ ìŠ¤íƒ€í‚¹.E412.150509.HDTV.H264.720p-WITH.par2" yEnc',
  445
), (
  1155,
  '^alt\\.binaries\\.cores$',
  '/^(?P<match0>.+?) - \\[\\d+\\/\\d+\\] - ".+?" yEnc$/',
  1,
  'Gracepoint S01E04 Episode Four 1080p WEB-DL DD51 H 264 CtrlHD - [52/52] - "Gracepoint S01E04 Episode Four 1080p WEB-DL DD51 H 264 CtrlHD.vol511+021.par2" yEnc',
  110
), (
  1156,
  '^alt\\.binaries\\.howard-stern$',
  '/^.+? \\[\\d+\\/\\d+\] "(?P<match0>.+?)\\.(mp3|nzb).*?" yEnc$/',
  1,
  'Artie Lange ArtieQuitter Podcast [2/9] "ArtieQuiterPodcast_2015-11-05_161_CF_128k.mp3.par2" yEnc',
  5
);