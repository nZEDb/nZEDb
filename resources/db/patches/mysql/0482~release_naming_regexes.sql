#Remove previous regex
DELETE FROM release_naming_regexes WHERE id IN (30, 31, 35, 749, 750, 751, 1160, 1161, 1162);

#Adjust previous regex
UPDATE release_naming_regexes SET id = id - 2 WHERE id IN (32, 33, 34) ORDER BY 'id' ASC;
UPDATE release_naming_regexes SET ordinal = 20 WHERE id = 28;
UPDATE release_naming_regexes SET ordinal = 25 WHERE id = 29;
UPDATE release_naming_regexes SET ordinal = 30 WHERE id = 30;
UPDATE release_naming_regexes SET ordinal = 35 WHERE id = 31;
UPDATE release_naming_regexes SET ordinal = 40 WHERE id = 32;

#Add updated regex
INSERT INTO release_naming_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  33,
  '^alt\\.binaries\\.(multimedia\\.|)(anime|cartoons)\\.?(highspeed|repost)?$',
  '/^[[(]\\d+\\/\\d+[])] - " ?(?P<match0>.+?) ?[. ](7z|avi|md5|mkv|mp4|nzb|par|vol)t?\\d+.+yEnc$/',
  1,
  '//[01/17] - "[neko-raws] Niji-iro Days 02 [BD][1080p][FLAC][768CC18E]v2.par2" - 590,59 MB yEnc',
  5
), (
  34,
  '^alt\\.binaries\\.(multimedia\\.|)(anime|cartoons)\\.?(highspeed|repost)?$',
  '/^.+\\" ?[ .-]?(?P<match0>.+?) ?[ .](7z|avi|md5|mkv|mp4|nzb|par|vol)t?\\d?+.+yEnc$/',
  1,
  '//[SpaceFish] Galilei Donna - Batch [BD][720p][MP4][AAC] [1/7] - "[SpaceFish] Galilei Donna - 07 [BD][720p][AAC] mp4" yEnc',
  10
), (
  35,
  '^alt\\.binaries\\.(multimedia\\.|)(anime|cartoons)\\.?(highspeed|repost)?$',
  '/^.+\\" ?[ .-]?(?P<match0>.+?) ?[ .](7z|avi|md5|mkv|mp4|nfo|nzb|par|vol)t?\\d?+.+[[(]\\d+\\/\\d+[])]$/',
  1,
  '//My Hero Academia Textless Opening Song \'THE DAY\' (BD AVC 1080p FLAC) [6D660059] - "My Hero Academia Textless Opening Song \'THE DAY\' (BD AVC 1080p FLAC) [6D660059] nfo" yEnc (01/35)',
  15
);

UPDATE release_naming_regexes SET id = id - 3 WHERE id BETWEEN 752 AND 1159 ORDER BY 'id' ASC;
