ALTER TABLE  `category` ADD  `minsize` BIGINT UNSIGNED NOT NULL DEFAULT '0';

UPDATE `site` set `value` = '56' where `setting` = 'sqlpatch';
