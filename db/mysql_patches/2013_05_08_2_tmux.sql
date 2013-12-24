INSERT IGNORE INTO `tmux`
    (`setting`, `value`)
    VALUES
    ('OPTIMIZE','FALSE'),('OPTIMIZE_TIMER','86400');


UPDATE `site` set `value` = '28' where `setting` = 'sqlpatch';
