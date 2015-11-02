ALTER TABLE binaryblacklist MODIFY COLUMN description VARCHAR(1000) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;

INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description)
VALUES (
	14,
	'^alt\\.binaries\\.multimedia\\.korean$',
	'TESTMAN',
	2,
	1,
	1,
	'Posts by TESTMAN (jpegs)'
), (
	15,
	'^alt\\.binaries\\.multimedia\\.korean$',
	'^yEnc ".+torrent"$',
	1,
	1,
	1,
	'torrent uploads ::: yEnc "SBS ÃÃÂ±Ã¢Â°Â¡Â¿Ã¤.E690.120916.HDTV.H264.720p-KOR.avi.torrent"'
), (
	16,
	'^korea\\.binaries\\.movies$',
	'^.\[?(Kornet|SK|xpeed|KT)\]?',
	1,
	1,
	1,
	'Incomplete releases'
), (
	17,
	'^korea\\.binaries\\.movies$',
	'^(top@top.t \\(top\\)|shit@xxxxxxxxaa.com \\(shit\\)|none@nonemail.com \\(none\\))$',
	2,
	1,
	1,
	'incomplete cryptic releases'
), (
	18,
	'^korea\\.binaries\\.movies$',
	'^filzilla6@web\\.de \\(Baruth\\)$',
	2,
	1,
	1,
	'Virus Poster'
);
