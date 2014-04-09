INSERT IGNORE INTO category (ID, title, parentID) VALUES (5010, 'WEB\-DL', 5000);

UPDATE `site` set `value` = '21' where `setting` = 'sqlpatch';
