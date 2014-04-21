ALTER TABLE sharing ADD COLUMN max_download MEDIUMINT UNSIGNED NOT NULL DEFAULT '150';

UPDATE sharing SET max_pull = 20000;

UPDATE site SET value = '202' WHERE setting = 'sqlpatch';
