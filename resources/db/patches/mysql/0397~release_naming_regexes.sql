ALTER TABLE release_naming_regexes
	MODIFY COLUMN description VARCHAR(1000) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	MODIFY COLUMN regex VARCHAR(2000) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

INSERT INTO release_naming_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
	1134,
	'^korea\\.binaries\\.music\\.videos)$',
	'/^\\[KoreanMusic\\] \\[(?P<match0>(뮤직뱅크|스케치북|음악중심|인기가요|더\\.쇼))\\](?P<match1>\\.\\d{6}\\..+?) \\[\\d+\\/\\d+\\] - ".+?" yEnc$/',
	1,
	'[KoreanMusic] [더.쇼].150730.워너비.전체.차렷.ts [5/16] - "[더.쇼].150730.워너비.전체.차렷.part04.rar" yEnc',
	0
), (
	1135,
	'^korea\\.binaries\\.music\\.videos$',
	'/^\\[KoreanMusic\\] (?P<match1>\\d{8}_)(?P<match0>(Simply\\.K-POP|쇼챔|더쇼|위열|음중|엠카)_)(?P<match2>.*?)_FHD_M2T \\[\\d+\\/\\d+\\] - ".+?" yEnc$/',
	1,
	'[KoreanMusic] 20151009_Simply.K-POP_GI-Doligo.Doligo_FHD_M2T [11/18] - "20151009_Simply.K-POP_GI-Doligo.Doligo_FHD_M2T.part09.rar" yEnc',
	1
), (
	1136,
	'^korea\\.binaries\\.movies$',
	'/^\\[KoreanMovies\\] (?P<match0>.+?) \\[\\d+\\/\\d+\\] - ".+" yEnc$/',
	1,
	'[KoreanMovies] 12.Deep.Red.Nights.2013.AVC1.H264.720p-UNknown [11/69] - "12 Deep Red Nights 2013.AVC1.H264.720p-UNknown.part09.rar" yEnc',
	6
), (
	1137,
	'^(korea\\.binaries\\.tv|alt\\.binaries\\.multimedia\\.korean)$',
	'/^\\[KoreanTV] (\\[.+?]\\.)?(?P<match0>.+?) \\[\\d+\\/\\d+\\] - ".+?" yEnc$/',
	1,
	'[KoreanTV] Hyde.Jekyll.Me.E15.150311.HDTV.H264.720p-WITH [20/31] - "Hyde.Jekyll.Me.E15.150311.HDTV.H264.720p-WITH.part19.rar" yEnc ::: [KoreanTV] [OCN].My.Beautiful.Bride.E16.END.150809.HDTV.H264.720p-WITH.mp4 [31/32] - "[OCN] 아름다운 나의신부.E16.END.150809.HDTV.H264.720p-WITH.vol15+16.par2" yEnc',
	0
), (
	1138,
	'^(korea\\.binaries\\.tv|alt\\.binaries\\.multimedia\\.korean)$',
	'/^.+?-enjoy-\\s+\\[\\d+\\/\\d+\\] - "(?P<match0>.+?)\\.(mkv|mp4|avi|jpe?g|par2|nzb|nfo|iso|ts|part\\d+\\.rar|vol\\d+\\+\\d+\\.par2|rar)(\\.\\d+)?" yEnc$/i',
	1,
	'Take Care of the Young Lady 2009 720p Completed -enjoy- [542/630] - "Take Care of the Young Lady.E06.720p.HDTV.x264-Zeus.vol000+001.PAR2" yEnc ::: Dream High 2 E01 720p -enjoy- [01/40] - "Dream.High.2.E01.120130.HDTV.X264.720p-HANrel.par2" yEnc',
	10
), (
	1139,
	'^(korea\\.binaries\\.tv|alt\\.binaries\\.multimedia\\.korean)$',
	'/^.+?-enjoy-\\s+- File \\d+ of \\d+: "(?P<match0>.+?)\\.(mkv|mp4|avi|jpe?g|par2|nzb|nfo|iso|ts|part\\d+\\.rar|vol\\d+\\+\\d+\\.par2|rar)(\\.\\d+)?" yEnc$/i',
	1,
	'[MV] C-REAL - No No No No No [2011.10.12] 1080p WEB MP4 -enjoy- - File 03 of 15: "C-real-no.no.no.no.part1.rar" yEnc',
	70
), (
	1140,
	'^(korea\\.binaries\\.tv|alt\\.binaries\\.multimedia\\.korean)$',
	'/^.+?-enjoy-\\s+\\(\\d+\\/\\d+\\) "(?P<match0>.+)$/',
	1,
	'Queen Seon Deok 720p Completed -enjoy- (1538/2482) "Queen Seon Deok.E39.091005.HDTV.X264.720p-HAN',
	40
), (
	1141,
	'^alt\\.binaries\\.multimedia\\.korean$',
	'/^\\(\\d+\\/\\d+\\) "(?P<match0>.+?)\\.(par2|part\\d+\\.rar)" - .+yEnc$/',
	1,
	'(01/24) "IRIS E02 091015 HDTV X264 720p-HANÂ™.par2" - 1.60 GB - ????(IRIS) E02 091015 HDTV X264 720p-HANÂ™ with eng subs yEnc',
	45
), (
	1142,
	'^alt\\.binaries\\.multimedia\\.korean$',
	'/^alt\\.binaries\\.newmiyamoto - .+? \\[\\d+\\/\\d+\\] - "(?P<match0>.+?)\\.(vol\\d+\\+\\d+\\.par2|avi|mkv)(\\.\\d+)?" yEnc$/',
	1,
	'alt.binaries.newmiyamoto - for scr3wtard and the canuck [002/276] - "Delightful Girl Choon-Hyang .vol000+561.par2" yEnc',
	20
), (
	1143,
	'^alt\\.binaries\\.multimedia\\.korean$',
	'/^(\\(|\\[)OMNi(\\)|\\]) (alt\\.binaries\\.newmiyamoto - )?.+?\\[\\d+\\/\\d+] - "(?P<match0>.+?)\\.(avi|mkv|vol\\d+\\+\\d+|iso|t(s|p))(\\.(par2|\\d+))?" yEnc$/i',
	1,
	'(OMNi) alt.binaries.newmiyamoto - Gourmet 720p RAW - 19-24 - [052/206] - "Sikgaek.E20.720p.HDTV.x264-jinuki.mkv.011" yEnc ::: (OMNi) alt.binaries.newmiyamoto - Fantasy Couple 720p - softsubbed - [002/584] - "Fantasy.Couple.01-02.vol00+01.PAR2" yEnc ::: (OMNi) Korean Kdrama of Hana Yori Dango - [02/70] - "Boys.Before.Flowers.E01.720p.HDTV.x264-NotoriouS.mkv.001" yEnc',
	21
), (
	1144,
	'^alt\\.binaries\\.multimedia\\.korean$',
	'/^\\(.+?\\) \\[\\d+\\/\\d+] - "(?P<match0>.+)\\[T1\\]\\.avi" yEnc$/',
	1,
	'(Winter Sonata) [12/34] - "Winter_Sonata_ep-10[T1].avi" yEnc',
	60
), (
	1145,
	'^alt\\.binaries\\.multimedia\\.korean$',
	'/^\\[KDrama\\].+? \\[\\d+\\/\\d+\\] - "(?P<match0>.+?)\\.(nzb|par2|r\\d+)" yEnc$/',
	1,
	'[KDrama]Secret.Garden.450p-HANrel [00/12] - "Secret.Garden.New.Year.Special.Part2.HDTV.X264.450p-HANrel.nzb" yEnc',
	50
), (
	1146,
	'^alt\\.binaries\\.multimedia\\.korean$',
	'/^\\[Album\\] (?P<match0>.+?) -enjoy- .+" yEnc$/',
	1,
	'[Album] Secret - Moving In Secret [2011.10.18] MP3 320 WEB -enjoy- - File 02 of 20: "02 섹시하게.mp3" yEnc',
	65
), (
	1147,
	'^alt\\.binaries\\.multimedia\\.korean$',
	'/^\\[\\?{4}\\]  - "(?P<match0>.+?)\\.mp3" \\[\\d+\\/\\d+\\] yEnc$/',
	1,
	'[????] - "Elmio-09-You Will Follow.mp3" [009/130] yEnc',
	55
), (
	1148,
	'^alt\\.binaries\\.multimedia\\.korean$',
	'/^\\(\\d+\\/\\d+\\) "(?P<match0>.+?)\\..+?" - \\d+\\.\\d+ .+ yEnc$/',
	1,
	'(1/1) "100612.MBC MusicCore.Just Married.Bye Bye Bye.60fps.x264-Izo.mkv" - 150.66 MB - 100612.MBC MusicCore.Double K.Favorite Music.60fps.x264-Izo yEnc',
	35
), (
	1149,
	'^korea\\.binaries\\.tv$',
	'/^.+? -?"(?P<match0>.+?)\\.(nzb|par2|vol\\d+\\+\\d+\\.par2|part\\d+\\.rar|r\\d+|rar)" \\[\\d+\\/\\d+\\] yEnc$/',
	1,
	'Posts by AREA11 ::: Sunbi18 "Scholar.Who.Walks.the.Night.E18.720p.HDTV.x264-AREA11.nzb" [00/56] yEnc',
	15
), (
	1150,
	'^(korea\\.binaries\\.(tv|movies)|alt\\.binaries\\.multimedia\\.korean)$',
	'/^\\((?P<match0>.+?)\\) \\[\\d+\\/\\d+\\] - ".+?" yEnc$/',
	1,
	'(BMask.E25.120829.HDTV.H264.720p-HANrel) [00/44] - "BMask.E25.nzb" yEnc',
	30
), (
	1151,
	'^korea\\.binaries\\.tv$',
	'/^"(?P<match0>\\w.+)\\.(par2|nzb|part\\d+\\.rar)" \\[\\d+\\/\\d+\\] yEnc$/',
	1,
	'"Queen.InHyun\'s.Man.E16.END.720p.HDTV.x264-AREA11.nzb" [00/40] yEnc',
	25
), (
	1152,
	'^korea\\.binaries\\.music\\.videos$',
	'/^\\[KoreanMusic\\] \\[.+?\\]\\.(?P<match1>.+?)(?P<match0>(MCD|The\\.Show)\\.\\d{6}\\.)(?P<match2>1080i\\.HDMI) \\[\\d+\\/\\d+\\] - ".+?" yEnc$/',
	1,
	'[KoreanMusic] [Mnet].NS윤지-Wifey.MCD.150409.1080i.HDMI [5/16] - "[Mnet].NS윤지-Wifey.MCD.150409.1080i.HDMI.part04.rar" yEnc',
	2
);
