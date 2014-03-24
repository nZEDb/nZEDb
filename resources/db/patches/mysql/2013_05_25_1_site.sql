INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('processvideos', 0);

ALTER TABLE  `releases` ADD  `videostatus` TINYINT(1) NOT NULL DEFAULT '0' AFTER  `jpgstatus` ;

UPDATE `site` set `value` = '50' where `setting` = 'sqlpatch';
