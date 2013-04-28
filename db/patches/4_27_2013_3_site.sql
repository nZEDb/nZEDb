INSERT IGNORE INTO `site`
    (`setting`, `value`)
    VALUES
	('backfillthreads','1'),
	('binarythreads','1'),
	('postthreads','1');

UPDATE `site` set `value` = '5' where `setting` = 'sqlpatch';

