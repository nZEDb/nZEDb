INSERT IGNORE INTO site (setting, value) values ('grabnzbthreads', '1');

UPDATE `site` set `value` = '96' where `setting` = 'sqlpatch';
