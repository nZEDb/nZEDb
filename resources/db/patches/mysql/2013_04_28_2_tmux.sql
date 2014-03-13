INSERT IGNORE INTO `tmux`
    (`setting`, `value`)
    VALUES
    ('SEQUENTIAL','FALSE');

INSERT IGNORE INTO `site`
    (`setting`, `value`)
    VALUES
    ('backfillthreads','1'),
	('binarythreads','1'),
	('postthreads','1'),
    ('nzbthreads','1');

UPDATE `site` set `value` = '10' where `setting` = 'sqlpatch';
