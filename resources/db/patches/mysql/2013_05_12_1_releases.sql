ALTER TABLE  `movieinfo` ADD  `type` VARCHAR( 32 ) NOT NULL AFTER  `genre` ;

UPDATE `site` set `value` = '33' where `setting` = 'sqlpatch';
