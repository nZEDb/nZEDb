INSERT IGNORE INTO category (ID, title, parentID) VALUES (3060, 'Foreign', 3000);

INSERT IGNORE INTO category (ID, title, parentID) VALUES (8060, 'Foreign', 8000);

UPDATE `site` set `value` = '40' where `setting` = 'sqlpatch';
