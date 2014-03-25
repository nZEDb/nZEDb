INSERT IGNORE INTO `tmux`
    (`setting`, `value`)
    VALUES
    ('BACKFILL_TYPE','FALSE');


UPDATE `site` set `value` = '15' where `setting` = 'sqlpatch';
