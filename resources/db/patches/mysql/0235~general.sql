UPDATE releases r INNER JOIN predb p ON p.id = r.preid SET r.preid = 0, r.searchname = r.name, r.isrenamed = 0,
r.iscategorized = 0, r.rageid = -1, r.seriesfull = NULL, r.season = NULL, r.episode = NULL, r.tvtitle = NULL,
r.tvairdate = NULL, r.imdbid = NULL, r.musicinfoid = NULL, r.consoleinfoid = NULL, r.bookinfoid = NULL,
r.anidbid = NULL WHERE p.title = 'readme';

UPDATE predb set filename = '' where filename = 'readme';