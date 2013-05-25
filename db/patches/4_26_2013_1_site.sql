INSERT IGNORE INTO `site`
	(`setting`, `value`)
	VALUES
	('maxanidbprocessed', 100),
	('maxmusicprocessed', 150),
	('maxgamesprocessed', 150),
	('maxbooksprocessed', 300),
	('sqlpatch', 1);

UPDATE `site` set `value` = '1' where `setting` = 'sqlpatch';
