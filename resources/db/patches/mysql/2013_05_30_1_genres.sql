ALTER TABLE  `genres` ADD  `disabled` tinyint(1) NOT NULL default '0';

UPDATE `site` set `value` = '69' where `setting` = 'sqlpatch';
