INSERT IGNORE INTO `site`
	(`setting`, `value`)
	VALUES
	('releasecompletion', 0);

UPDATE `site` set `value` = '3' where `setting` = 'sqlpatch';
