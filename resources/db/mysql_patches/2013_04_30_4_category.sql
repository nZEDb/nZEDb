INSERT IGNORE INTO category (ID, title, parentID) VALUES (2070, 'DVD', 2000);

UPDATE `site` set `value` = '16' where `setting` = 'sqlpatch';
