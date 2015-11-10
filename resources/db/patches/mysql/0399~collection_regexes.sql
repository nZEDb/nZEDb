INSERT INTO collection_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  598,
  '^alt\\.binaries\\.e-book\\.technical$',
  '/^(?P<match0>(New )?tech eBooks -=?\\[?\\s?[\\w\\.-]+\\s?\\]?=?- )".+?" yEnc$/i',
  1,
  'New tech eBooks -[ Springer.Toxic.Trauma.A.Basic.Clinical.Guide.2014.RETAIL.EBOOK-METHLAB ]- "ml-0435a.zip" yEnc',
  5
), (
  599,
  '^alt\\.binaries\\.(cores|ath)$',
  '/^(?P<match0>\\[(SERIES|MOVIES)\\]-\\[ www\\.vip-lounge\\.me \\]) \\[\\d+\\/\\d+\\] - "(?P<match1>.+?)\\.(par2|nfo|rar|part\\d+\\.rar|vol\\d+\\+\\d+\\.par2)" - \\d+,\\d+ [MG]B yEnc$/i',
  1,
  '[SERIES]-[ www.vip-lounge.me ] [19/20] - "Wild.World.Africas.Deadly.Eden.480p.x264-mSD.vol03+4.par2" - 443,42 MB yEnc',
  55
), (
  600,
  '^alt\\.binaries\\.cores$',
  '/^(?P<match0>.+?) - \[\d+\/\d+\] - ".+?" yEnc$/',
  1,
  'Gracepoint S01E04 Episode Four 1080p WEB-DL DD51 H 264 CtrlHD - [52/52] - "Gracepoint S01E04 Episode Four 1080p WEB-DL DD51 H 264 CtrlHD.vol511+021.par2" yEnc',
  80
), (
  601,
  '^alt\\.binaries\\.howard-stern$',
  '/^(?P<match0>.+?) \\[\\d+\\/\\d+\\] "(?P<match1>.+?)\\.(mp3|nzb).*?" yEnc$/',
  1,
  'Artie Lange ArtieQuitter Podcast [2/9] "ArtieQuiterPodcast_2015-11-05_161_CF_128k.mp3.par2" yEnc',
  5
), (
  602,
  '^alt\\.binaries\\.howard-stern$',
  '/^(?P<match0>.+?) - File \\d+ of \\d+ - yEnc "(?P<match1>.+?).mp3" \\d+ bytes$/',
  1,
  'Howard Stern 11.04.2015 CF 32K 57.7MB + WUS - File 1 of 1 - yEnc "Howard Stern 11.04.15 (Alanis Morissette Visits).mp3" 60596352 bytes',
  10
), (
  603,
  '^alt\\.binaries\\.howard-stern$',
  '/^(?P<match0>.+?) \\d (?P<match1>\\d+kbps) - \\[\\d+\\/\\d+\\] - "(?P<match2>.+?)Part_\\d+\\.(mp3|nzb).*?" yEnc$/',
  1,
  'Howard Stern Show Oct 19 2015 Mon Hour 6 96kbps - [1/1] - "Stern-2015_10_19-96k-Part_06.mp3" yEnc',
  15
);
