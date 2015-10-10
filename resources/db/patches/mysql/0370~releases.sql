# This patch will create a temp table like releases
# Then it will alter the nzb_guid column to be BINARY(16)
# It will insert all data from releases into the temp table while unhexing nzb_guid
# Drop old releases table
# Rename new releases temp table to releases

CREATE TABLE releases_tmp LIKE releases;
ALTER TABLE releases_tmp MODIFY nzb_guid BINARY(16) NULL;
INSERT INTO releases_tmp (id, name, searchname, totalpart, group_id, size, postdate, adddate, updatetime, guid, fromname, completion, categoryid, rageid, seriesfull, season, episode, tvtitle, tvairdate, imdbid, xxxinfo_id, musicinfoid, consoleinfoid, gamesinfo_id, bookinfoid, anidbid, grabs, comments, passwordstatus, rarinnerfilecount, haspreview, nfostatus, jpgstatus, videostatus, audiostatus, dehashstatus, reqidstatus, nzb_guid, preid, nzbstatus, iscategorized, isrenamed, ishashed, isrequestid, proc_pp, proc_sorter, proc_par2, proc_nfo, proc_files)
SELECT id, name, searchname, totalpart, group_id, size, postdate, adddate, updatetime, guid, fromname, completion, categoryid, rageid, seriesfull, season, episode, tvtitle, tvairdate, imdbid, xxxinfo_id, musicinfoid, consoleinfoid, gamesinfo_id, bookinfoid, anidbid, grabs, comments, passwordstatus, rarinnerfilecount, haspreview, nfostatus, jpgstatus, videostatus, audiostatus, dehashstatus, reqidstatus, UNHEX(nzb_guid), preid, nzbstatus, iscategorized, isrenamed, ishashed, isrequestid, proc_pp, proc_sorter, proc_par2, proc_nfo, proc_files FROM releases;
DROP TABLE releases;
ALTER TABLE releases_tmp RENAME releases;