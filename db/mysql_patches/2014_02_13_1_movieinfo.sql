DROP INDEX ix_movieinfo_imdbid ON movieinfo;
DROP INDEX ix_consoleinfo_asin ON consoleinfo;
DROP INDEX ix_musicinfo_asin ON musicinfo;
DROP INDEX ix_bookinfo_asin ON bookinfo;

ALTER IGNORE TABLE movieinfo ADD UNIQUE INDEX ix_movieinfo_imdbid(imdbid);
ALTER IGNORE TABLE consoleinfo ADD UNIQUE INDEX ix_consoleinfo_asin(asin);
ALTER IGNORE TABLE musicinfo ADD UNIQUE INDEX ix_musicinfo_asin(asin);
ALTER IGNORE TABLE bookinfo ADD UNIQUE INDEX ix_bookinfo_asin(asin);

UPDATE site SET value = '176' WHERE setting = 'sqlpatch';
