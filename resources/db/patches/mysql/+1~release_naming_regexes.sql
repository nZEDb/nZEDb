DROP TABLE IF EXISTS release_naming_regexes;
CREATE TABLE release_naming_regexes (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  group_regex VARCHAR(255)  NULL                          COMMENT 'This is a regex to match against usenet groups',
  regex       VARCHAR(5000) NOT NULL                      COMMENT 'Regex used for extracting name from subject',
  status      TINYINT(1)    UNSIGNED NOT NULL DEFAULT '1' COMMENT '1=ON 0=OFF',
  description VARCHAR(1000) NOT NULL                      COMMENT 'Optional extra details on this regex',
  ordinal     INT SIGNED    NOT NULL DEFAULT '0'          COMMENT 'Order to run the regex in',
  PRIMARY KEY (id),
  INDEX ix_release_naming_regexes_group_regex (group_regex),
  INDEX ix_release_naming_regexes_status      (status),
  INDEX ix_release_naming_regexes_ordinal     (ordinal)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 100000;

INSERT INTO release_naming_regexes (id, group_regex, regex, status, description, ordinal)
  VALUES (
    1,
    'alt\\.binaries\\.teevee',
    '/^\\[#+\\]-\\[.+?\\]-\\[.+?\\]-\\[ (?P<match1>.+?) \\][- ]\\[\\d+\\/\\d+\\][-_\\s]{0,3}("|#34;).+?("|#34;) yEnc$/',
    1,
    '[######]-[FULL]-[#a.b.teevee@EFNet]-[ Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD ] [1/5] - "Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD.nfo" yEnc',
    5
), (
    2,
    'alt\\.binaries\\.teevee',
    '/^\\[#+\\]-\\[.+?\\]-\\[.+?\\]-\\[(?P<match1>.+?)\\][- ]\\[\\d+\\/\\d+\\][-_\\s]{0,3}".+?" yEnc$/',
    1,
    '[34148]-[FULL]-[#a.b.teevee@EFNet]-[Batman.The.Animated.Series.S04E01.DVDRiP.XviD-PyRo]-[00/35] "Batman.The.Animated.Series.S04E01.DVDRiP.XviD-PyRo.nzb" yEnc',
    10
), (
    3,
    'alt\\.binaries\\.teevee',
    '/^\\[#+\\]-\\[.+?\\]-\\[ (?P<match1>.+?) \\][- ]\\[\\d+\\/\\d+\\][-_\\s]{0,3}".+?" yEnc$/',
    1,
    '[38722]-[#a.b.foreign@EFNet]-[ Game.Of.Thrones.S01E01.Der.Winter.Naht.GERMAN.DL.WS.1080p.HDTV.x264-MiSFiTS ]-[01/37] - "misfits-gameofthrones1080-s01e01-sample-sample.par2" yEnc',
    15
), (
    4,
    'alt\\.binaries\\.teevee',
    '/^\\[#+\\]-\\[.+?\\]-\\[.+?\\]-\\[ (?P<match1>.+?) \\][- ]\\[\\d+\\/\\d+\\][-_\\s]{0,3}("|#34;).+?("|#34;) yEnc$/',
    1,
    '[#a.b.teevee] Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD - [01/24] - "Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD.nfo" yEnc',
    20
), (
    5,
    'alt\\.binaries\\.teevee',
    '/\\[#+\\]-\\[.+?\\]-\\[.+?\\]-\\[ ?(?P<match1>.+?) ?\\][- ]\\[\\d+\\/\\d+\\][-_\\s]{0,3}("|#34;).+?("|#34;) \\(\\d+\\/\\d+\\) \\(\\d+\\/\\d+$/',
    1,
    '[17319]-[FULL]-[#a.b.teevee@EFNet]-[ CSI.New.York.S05E22.720p.HDTV.X264-DIMENSION ]-[01/34] "csi.new.york.522.720p-dimension.nfo" (1/1) (1/1',
    25
);