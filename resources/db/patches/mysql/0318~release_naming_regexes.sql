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
    '/\\[\\d+\\]-\\[.+?\\]-\\[.+?\\]-\\[ (.+\\.S\\d\\dE\\d\\d\\..+?) \\][- ]\\[\d+\\/\\d+\\][ -]{0,3}"[\\w\\säöüÄÖÜß+¤¶!.,&_()\\[\\]\\\'\\`{}-]{8,}?\\b.?([-_](proof|sample|thumbs?))*(\\.part\\d*(\\.rar)?|\\.rar|\\.7z)?(\\d{1,3}\\.rev"|\\.vol.+?"|\\.[A-Za-z0-9]{2,4}"|")[-_\\s]{0,3}\d+([.,]\\d+)? [kKmMgG][bB][-_\\s]{0,3}[-_\\s]{0,3}yEnc$/ui',
    1,
    'rename these teevee releases as the requestid is for the full season ::: [169018]-[FULL]-[a.b.teevee]-[ House.of.Lies.S01E01.720p.WEB-DL.DD5.1.H.264-BS ]-[04/32] - "House.of.Lies.S01E01.The.Gods.of.Dangerous.Financial.Instruments.720p.WEB-DL.DD5.1.H.264-BS.part03.rar" yEnc',
    5
), (
    2,
    'alt\\.binaries\\.teevee',
    '/\\[\\d+\\]-\\[.+?\\]-\\[.+?\\]-\\[ .+\\.S\\d\\d\\..+? \\][- ]\\[\\d+\\/\\d+\\][ -]{0,3}"([\\w\\säöüÄÖÜß+¤¶!.,&_()\[\]\\\'\\`{}#-]{8,}?\\b.?)([-_](proof|sample|thumbs?))*(\\.part\\d*(\\.rar)?|\\.rar|\\.7z)?(\\d{1,3}\\.rev"|\\.vol.+?"|\\.[A-Za-z0-9]{2,4}"|")[-_\\s]{0,3}\d+([.,]\\d+)? [kKmMgG][bB][-_\\s]{0,3}[-_\\s]{0,3}yEnc$/ui',
    1,
    'Season only in 4th block so take filename ::: [169019]-[FULL]-[a.b.teevee]-[ House.of.Lies.S02.720p.WEB-DL.DD5.1.H.264-BS ]-[24/32] - "House.of.Lies.S02E02.When.Dinosaurs.Ruled.the.Planet.720p.WEB-DL.DD5.1.H.264-BS.vol000+01.par2" yEnc',
    10
), (
    3,
    'alt\\.binaries\\.teevee',
    '/^\\[#+\\]-\\[.+?\\]-\\[.+?\\]-\\[ (?P<match1>.+?) \\][- ]\\[\\d+\\/\\d+\\][-_\\s]{0,3}("|#34;).+?("|#34;) yEnc$/',
    1,
    '[######]-[FULL]-[#a.b.teevee@EFNet]-[ Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD ] [1/5] - "Misfits.S01.SUBPACK.DVDRip.XviD-P0W4DVD.nfo" yEnc',
    15
), (
    4,
    'alt\\.binaries\\.teevee',
    '/^\\[#+\\]-\\[.+?\\]-\\[.+?\\]-\\[(?P<match1>.+?)\\][- ]\\[\\d+\\/\\d+\\][-_\\s]{0,3}".+?" yEnc$/',
    1,
    '[34148]-[FULL]-[#a.b.teevee@EFNet]-[Batman.The.Animated.Series.S04E01.DVDRiP.XviD-PyRo]-[00/35] "Batman.The.Animated.Series.S04E01.DVDRiP.XviD-PyRo.nzb" yEnc',
    20
), (
    5,
    'alt\\.binaries\\.teevee',
    '/^\\[#+\\]-\\[.+?\\]-\\[ (?P<match1>.+?) \\][- ]\\[\\d+\\/\\d+\\][-_\\s]{0,3}".+?" yEnc$/',
    1,
    '[38722]-[#a.b.foreign@EFNet]-[ Game.Of.Thrones.S01E01.Der.Winter.Naht.GERMAN.DL.WS.1080p.HDTV.x264-MiSFiTS ]-[01/37] - "misfits-gameofthrones1080-s01e01-sample-sample.par2" yEnc',
    25
), (
    6,
    'alt\\.binaries\\.teevee',
    '/^\\[#+\\]-\\[.+?\\]-\\[.+?\\]-\\[ (?P<match1>.+?) \\][- ]\\[\\d+\\/\\d+\\][-_\\s]{0,3}("|#34;).+?("|#34;) yEnc$/',
    1,
    '[#a.b.teevee] Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD - [01/24] - "Parks.and.Recreation.S01E01.720p.WEB-DL.DD5.1.H.264-CtrlHD.nfo" yEnc',
    30
), (
    7,
    'alt\\.binaries\\.teevee',
    '/\\[#+\\]-\\[.+?\\]-\\[.+?\\]-\\[ ?(?P<match1>.+?) ?\\][- ]\\[\\d+\\/\\d+\\][-_\\s]{0,3}("|#34;).+?("|#34;) \\(\\d+\\/\\d+\\) \\(\\d+\\/\\d+$/',
    1,
    '[17319]-[FULL]-[#a.b.teevee@EFNet]-[ CSI.New.York.S05E22.720p.HDTV.X264-DIMENSION ]-[01/34] "csi.new.york.522.720p-dimension.nfo" (1/1) (1/1',
    35
);