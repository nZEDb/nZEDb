INSERT INTO release_naming_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
	1132,
	'^alt\\.binaries\\.moovee$',
	'#^(?P<name>[\\w.-]{8,}?) \\[\\d+/\\d+\\] - ".+?" yEnc$#',
	1,
	'Search.Party.2014.1080p.WEB-DL.DD5.1.H264-RARBG [01/57] - "Search.Party.2014.1080p.WEB-DL.DD5.1.H264-RARBG.nfo" yEnc',
	95
), (
	1133,
	'^alt\\.binaries\\.moovee$',
	'#^\\[FETiSH\]-(\\[REPOST\\]-)?\\[ (?P<name>.+?) \\]-\\[\\d+/\\d+\\] ".+?" yEnc$#',
	1,
	'[FETiSH]-[ In.Dreams.1999.DVDRip.x264-FETiSH ]-[32/40] " fetish-ids1999.vol000+001.PAR2 " yEnc',
	100
);