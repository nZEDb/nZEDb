ALTER TABLE  `releases` ADD  `jpgstatus` TINYINT(1) NOT NULL DEFAULT '0' AFTER  `relnamestatus` ;

INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('processjpg', 0);

UPDATE `site` set `value` = '43' where `setting` = 'sqlpatch';
