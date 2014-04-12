ALTER TABLE `users` ADD COLUMN firstname VARCHAR (255) AFTER username, ADD COLUMN lastname VARCHAR (255) AFTER firstname;

UPDATE `site` SET value = '159' WHERE setting = 'sqlpatch';
