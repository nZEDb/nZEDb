INSERT IGNORE INTO `site`
    (`setting`, `value`)
    VALUES
	('backfillthreads','1'),
	('binarythreads','1'),
	('postthreads','1');

UPDATE `site` set `value` = '11' where `setting` = 'sqlpatch';

