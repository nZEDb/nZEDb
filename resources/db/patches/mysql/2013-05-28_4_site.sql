INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('processaudiosample', 0);

ALTER TABLE `releases` ADD `audiostatus` TINYINT(1) NOT NULL DEFAULT '0' AFTER  `videostatus` ;

UPDATE `site` set `value` = '63' where `setting` = 'sqlpatch';
