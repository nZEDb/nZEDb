INSERT IGNORE INTO `tmux`
    (`setting`, `value`)
    VALUES
    ('SEQ_TIMER','30'),('BINS_TIMER','30'),('BACK_TIMER','30'),('IMPORT_TIMER','30'),('REL_TIMER','30'),('FIX_TIMER','30'),('POST_TIMER','30');


UPDATE `site` set `value` = '13' where `setting` = 'sqlpatch';
