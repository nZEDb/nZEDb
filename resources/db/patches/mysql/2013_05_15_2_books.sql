ALTER TABLE `bookinfo` ADD  `genre` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL AFTER  `overview` ;

UPDATE `site` set `value` = '35' where `setting` = 'sqlpatch';
