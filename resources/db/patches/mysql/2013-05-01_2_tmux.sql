INSERT IGNORE INTO `tmux`
    (`setting`, `value`)
    VALUES
    ('COLLECTIONS_KILL','0'), ('POSTPROCESS_KILL','0');


UPDATE `site` set `value` = '18' where `setting` = 'sqlpatch';
