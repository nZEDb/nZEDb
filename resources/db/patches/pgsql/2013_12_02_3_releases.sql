ALTER TABLE `releases` ALTER COLUMN `bitwise` TYPE integer;

UPDATE site SET value = '154' WHERE setting = 'sqlpatch';
