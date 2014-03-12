ALTER TABLE anidb ALTER COLUMN startdate DROP NOT NULL
ALTER TABLE anidb ALTER COLUMN enddate DROP NOT NULL


UPDATE site SET value = '165' WHERE setting = 'sqlpatch';
