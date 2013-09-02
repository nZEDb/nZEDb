INSERT INTO `site` (`ID`, `setting`, `value`, `updateddate`) VALUES (NULL, 'currentppticket', '0', CURRENT_TIMESTAMP), (NULL, 'nextppticket', '0', CURRENT_TIMESTAMP);
ALTER TABLE  `releases` ADD  `updatetime` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL AFTER `adddate` ;
UPDATE `releases` SET `passwordstatus` = 10 WHERE `passwordstatus` = 2;
UPDATE `releases` SET `passwordstatus` = 2 WHERE `passwordstatus` = 3;

UPDATE `site` set `value` = '84' where `setting` = 'sqlpatch';
