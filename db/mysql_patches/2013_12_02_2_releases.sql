ALTER TABLE `releases` CHANGE `bitwise` `bitwise` SMALLINT NOT NULL DEFAULT 0;

UPDATE site SET value = '153' WHERE setting = 'sqlpatch';
