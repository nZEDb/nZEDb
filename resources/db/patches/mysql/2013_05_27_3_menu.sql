INSERT IGNORE INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('predb', 'PreDB', 'Browse PreDB.', 1, 51);

UPDATE `site` set `value` = '58' where `setting` = 'sqlpatch';
