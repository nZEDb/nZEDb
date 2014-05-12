ALTER TABLE `anidb` CHANGE startdate startdate DATE DEFAULT NULL;
ALTER TABLE `anidb` CHANGE enddate enddate DATE DEFAULT NULL;

UPDATE site SET value = '165' WHERE setting = 'sqlpatch';
