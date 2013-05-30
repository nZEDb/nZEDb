ALTER TABLE  `genre` ADD  `disablepreview` tinyint(1) NOT NULL default '0';

UPDATE `site` set `value` = '61' where `setting` = 'sqlpatch';


