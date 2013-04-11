INSERT INTO site (setting, value) VALUES ('sabintegrationtype', 2);
INSERT INTO site (setting, value) VALUES ('saburl', 'http://localhost:8080/sabnzbd/');
INSERT INTO site (setting, value) VALUES ('sabapikey', '');
INSERT INTO site (setting, value) VALUES ('sabapikeytype', 1);
INSERT INTO site (setting, value) VALUES ('sabpriority', 0);

UPDATE menu SET menueval = '{if $sabapikeytype!=2}-1{/if}' WHERE href = 'queue';

ALTER TABLE `users`  
ADD `saburl` VARCHAR(255) NULL DEFAULT NULL AFTER `consoleview`,  
ADD `sabapikey` VARCHAR(255) NULL DEFAULT NULL AFTER `saburl`,  
ADD `sabapikeytype` TINYINT(1) NULL DEFAULT NULL AFTER `sabapikey`,  
ADD `sabpriority` TINYINT(1) NULL DEFAULT NULL AFTER `sabapikeytype`;