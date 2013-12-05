ALTER TABLE `anidb` DROP COLUMN id;
DROP INDEX IF EXISTS "anidbid" CASCADE;
ALTER TABLE "anidb" ADD PRIMARY KEY("anidbid");
ALTER TABLE `anidb` ADD COLUMN imdbid integer NOT NULL DEFAULT 0, ADD COLUMN tvdbid integer NOT NULL DEFAULT 0;

UPDATE site SET value = '155' WHERE setting = 'sqlpatch';
