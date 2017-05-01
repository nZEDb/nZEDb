INSERT INTO release_naming_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
    1161,
    '^alt\\.binaries\\.(anime|multimedia.anime)\\.?(highspeed|repost)?',
    '/^[[(]\\d+\\/\\d+[])] - "(?P<match0>.+?)[. ](7z|avi|mkv|mp4|par|vol)t?\\d+.+yEnc$/',
    1,
    '//[01/17] - "[neko-raws] Niji-iro Days 02 [BD][1080p][FLAC][768CC18E]v2.par2" - 590,59 MB yEnc',
    45
), (
    1162,
    '^alt\\.binaries\\.(anime|multimedia.anime)\\.?(highspeed|repost)?',
    '/^.+\\"[ .-]?(?P<match0>.+?)[ .](7z|avi|mkv|mp4|par|vol)t?\\d?+.+yEnc$/',
    1,
    '//[SpaceFish] Galilei Donna - Batch [BD][720p][MP4][AAC] [1/7] - "[SpaceFish] Galilei Donna - 07 [BD][720p][AAC] mp4" yEnc',
    50
);
