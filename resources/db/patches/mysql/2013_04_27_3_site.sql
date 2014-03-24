INSERT IGNORE INTO `site`
    (`setting`, `value`)
    VALUES
	('backfillthreads','4'),
	('binarythreads','4'),
	('postthreads','4');

UPDATE `site` set `value` = '5' where `setting` = 'sqlpatch';
