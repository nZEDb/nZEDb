INSERT INTO release_naming_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
	1130,
	'^alt\\.binaries\\.teevee$',
	'#^\[KoreanTV\]\s*(?P<match0>.+?)\.[a-z0-9]+\s+\[\d+/\d+\]\s+-\s+".+?"\s+yEnc$#i',
	1,
	'[KoreanTV] Star.King.E412.150509.HDTV.H264.720p-WITH.mp4 [1/36] - "ë†€ë�¼ìš´ ëŒ€íšŒ ìŠ¤íƒ€í‚¹.E412.150509.HDTV.H264.720p-WITH.par2" yEnc',
	445
), (
	1131,
	'^alt\\.binaries\\.fz',
	'#^Uploader.Presents-(?P<match0>.+?)\\s+\\[\\d{1,3}/\\d{1,3}\\]\\s*".+?"\\s+yEnc$#i',
	1,
	'Uploader.Presents-Black.Sails.S02E02.Die.schwarze.Flagge.GERMAN.DUBBED.BLURAYRiP.x264-SOF [00/23]"sof-black-sails-s02e02.nzb" yEnc',
	10
);