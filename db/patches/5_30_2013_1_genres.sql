ALTER TABLE  `genres` ADD  `disablepreview` tinyint(1) NOT NULL default '0';

UPDATE `site` set `value` = '69' where `setting` = 'sqlpatch';
