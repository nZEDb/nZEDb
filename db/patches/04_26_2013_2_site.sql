INSERT IGNORE INTO `site`
	(`setting`, `value`)
	VALUES
	('maxnzbsprocessed', 1000);

UPDATE `site` set `value` = '2' where `setting` = 'sqlpatch';
