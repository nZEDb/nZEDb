DROP INDEX ix_movieinfo_imdbid ON movieinfo;
ALTER IGNORE TABLE movieinfo ADD UNIQUE INDEX ix_movieinfo_imdbid(imdbid);
ALTER IGNORE TABLE consoleinfo ADD UNIQUE INDEX ix_consoleinfo_asin(asin);
ALTER IGNORE TABLE musicinfo ADD UNIQUE INDEX ix_musicinfo_asin(asin);

UPDATE site SET value = '175' WHERE setting = 'sqlpatch';
