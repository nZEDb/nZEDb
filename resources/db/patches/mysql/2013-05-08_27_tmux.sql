INSERT IGNORE INTO `tmux`
    (`setting`, `value`)
    VALUES
    ('BACKFILL_GROUPS','4'),('POST_KILL_TIMER','300');


UPDATE `site` set `value` = '27' where `setting` = 'sqlpatch';
