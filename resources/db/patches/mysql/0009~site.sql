INSERT IGNORE INTO `site`
    (`setting`, `value`)
    VALUES
	('releasethreads','1');

INSERT IGNORE INTO `tmux`
    (`setting`, `value`)
    VALUES
    ('RELEASES_THREADED','FALSE');

UPDATE `site` set `value` = '9' where `setting` = 'sqlpatch';
