DROP INDEX ix_movieinfo_imdbid ON movieinfo;
DROP INDEX ix_consoleinfo_asin ON consoleinfo;
DROP INDEX ix_musicinfo_asin ON musicinfo;
DROP INDEX ix_bookinfo_asin ON bookinfo;

CREATE UNIQUE INDEX ix_movieinfo_imdbid ON movieinfo(imdbid);
CREATE UNIQUE INDEX ix_consoleinfo_asin ON consoleinfo(asin);
CREATE UNIQUE INDEX ix_musicinfo_asin ON musicinfo(asin);
CREATE UNIQUE INDEX ix_bookinfo_asin ON bookinfo(asin);

UPDATE site SET value = '176' WHERE setting = 'sqlpatch';
