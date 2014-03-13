ALTER TABLE `releasefiles` ADD UNIQUE KEY `name` (`name`, `releaseID`);

UPDATE `site` set `value` = '105' where `setting` = 'sqlpatch';
