ALTER TABLE `anidb` DROP COLUMN id;
ALTER TABLE `anidb` DROP INDEX anidbid;
ALTER TABLE `anidb` ADD PRIMARY KEY (anidbid);
ALTER TABLE `anidb` ADD COLUMN imdbid INT(7) NOT NULL AFTER anidbid, ADD COLUMN tvdbid INT(7) NOT NULL AFTER imdbid;

UPDATE site SET value = '155' WHERE setting = 'sqlpatch';
