ALTER TABLE `releases` CHANGE `bitwise` `bitwise` SMALLINT UNSIGNED NOT NULL DEFAULT 0;

UPDATE site SET value = '154' WHERE setting = 'sqlpatch';
