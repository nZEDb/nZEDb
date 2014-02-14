DROP INDEX ix_movieinfo_imdbid ON movieinfo;
CREATE UNIQUE INDEX ix_movieinfo_imdbid ON movieinfo(imdbid);
CREATE UNIQUE INDEX ix_consoleinfo_imdbid ON mconsoleinfo(imdbid);
CREATE UNIQUE INDEX ix_musicinfo_imdbid ON musicinfo(imdbid);

UPDATE site SET value = '175' WHERE setting = 'sqlpatch';
