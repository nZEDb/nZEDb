DROP TABLE IF EXISTS collection_regexes;
CREATE TABLE collection_regexes (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  group_regex VARCHAR(255)  NULL                          COMMENT 'This is a regex to match against usenet groups',
  regex       VARCHAR(5000) NOT NULL                      COMMENT 'Regex used for collection grouping',
  status      TINYINT(1)    UNSIGNED NOT NULL DEFAULT '1' COMMENT '1=ON 0=OFF',
  description VARCHAR(1000) NOT NULL                      COMMENT 'Optional extra details on this regex',
  ordinal     INT SIGNED    NOT NULL DEFAULT '0'          COMMENT 'Order to run the regex in',
  PRIMARY KEY (id),
  INDEX ix_collection_regexes_group_regex (group_regex),
  INDEX ix_collection_regexes_status      (status),
  INDEX ix_collection_regexes_ordinal     (ordinal)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 100000;

INSERT INTO collection_regexes (id, group_regex, regex, status, description, ordinal)
  VALUES (
    1,
    'alt\\.binaries\\.teevee',
    '/(?P<match1>\\[[\\d#]+\\]-\\[.+?\\]-\\[.+?\\])-\\[ (?P<match2>.+?) \\][- ]\\[\\d+\\/\\d+\\] - ".+?" yEnc$/i',
    1,
    '[278997]-[FULL]-[#a.b.erotica]-[ chi-the.walking.dead.xxx ]-[06/51] - "chi-the.walking.dead.xxx-s.mp4" yEnc ::: [######]-[FULL]-[#a.b.teevee@EFNet]-[ Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD ] [1/5] - "Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD.nfo" yEnc ::: Re: [147053]-[FULL]-[#a.b.teevee]-[ Top_Gear.20x04.HDTV_x264-FoV ]-[11/59] - "top_gear.20x04.hdtv_x264-fov.r00" yEnc',
    0
), (
    2,
    'alt\\.binaries\\.teevee',
    '/(?P<match1>\\[[\\d#]+\\]-\\[.+?\\]-\\[.+?\\])-\\[ (?P<match2>.+?) \\][ -]{0,3}".+?" yEnc$/i',
    1,
    '[185409]-[FULL]-[a.b.teeveeEFNet]-[ Dragon.Ball.Z.S03E24.1080p.WS.BluRay.x264-CCAT ]-"dragon.ball.z.s03e24.1080p.ws.bluray.x264-ccat.nfo" yEnc',
    1
), (
    3,
    'alt\\.binaries\\.teevee',
    '/^(?P<match1>\\[#a\\.b\\.teevee\\] .+? - \\[)\\d+\\/\\d+\\] - ".+?" yEnc$/',
    1,
    '[#a.b.teevee] Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD - [01/24] - "Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD.nfo"',
    2
), (
    4,
    'alt\\.binaries\\.teevee',
    '/^(?P<match1>[a-z0-9]+ - )\\[\\d+\\/\\d+\\] - "[a-z0-9]+\\..+?" yEnc$/',
    1,
    'ah63jka93jf0jh26ahjas558 - [01/22] - "ah63jka93jf0jh26ahjas558.par2" yEnc',
    3
), (
    5,
    'alt\\.binaries\\.teevee',
    '/^(?P<match1>[a-z0-9]+ \\()\\d+\\/\\d+\\) ".+?" - \\d+[,.]\\d+ [mMkKgG][bB] - yEnc$/',
    1,
    'fhdbg34rgjdsfd008c (42/43) "fhdbg34rgjdsfd008c.vol062+64.par2" - 3,68 GB - yEnc',
    4
), (
    6,
    'alt\\.binaries\\.teevee',
    '/^(?P<match1>[a-zA-Z0-9]+)\\[\\d+\\/\\d+\\] - ".+?" yEnc$/',
    1,
    't2EI3CdWdF0hi5b8L9tkx[08/52] - "t2EI3CdWdF0hi5b8L9tkx.part07.rar" yEnc',
    5
);