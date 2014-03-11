UPDATE category SET title = 'Phone\-Other' WHERE ID = '4040';

INSERT IGNORE INTO category (ID, title, parentID) VALUES (4060, 'Phone\-IOS', 4000);

INSERT IGNORE INTO category (ID, title, parentID) VALUES (4070, 'Phone\-Android', 4000);

INSERT IGNORE INTO category (ID, title, parentID) VALUES (6060, 'Imageset', 6000);

INSERT IGNORE INTO category (ID, title, parentID) VALUES (6070, 'Packs', 6000);

UPDATE `site` set `value` = '7' where `setting` = 'sqlpatch';
