DROP TABLE IF EXISTS collection_regexes;
CREATE TABLE collection_regexes (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  group_regex VARCHAR(255)  NULL                          COMMENT 'This is a regex to match against usenet groups',
  regex       VARCHAR(5000) NOT NULL                      COMMENT 'Regex used for collection grouping'
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
);

INSERT INTO collection_regexes (id, group_regex, regex, status, description, ordinal)
  VALUES (
    2,
    'alt\\.binaries\\.teevee',
    '/(?P<match1>\\[[\d#]+\\]-\\[.+?\\]-\\[.+?\\])-\\[ (?P<match2>.+?) \\][ -]{0,3}".+?" yEnc$/i',
    1,
    '[185409]-[FULL]-[a.b.teeveeEFNet]-[ Dragon.Ball.Z.S03E24.1080p.WS.BluRay.x264-CCAT ]-"dragon.ball.z.s03e24.1080p.ws.bluray.x264-ccat.nfo" yEnc',
    1
);