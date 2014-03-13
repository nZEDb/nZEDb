INSERT IGNORE INTO `tmux`
    (`setting`, `value`)
    VALUES
    ('BACKFILL_QTY','100000');


UPDATE `site` set `value` = '17' where `setting` = 'sqlpatch';
