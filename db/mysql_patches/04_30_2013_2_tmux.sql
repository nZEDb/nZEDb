INSERT IGNORE INTO `tmux`
    (`setting`, `value`)
    VALUES
    ('IMPORT_BULK','FALSE');


UPDATE `site` set `value` = '14' where `setting` = 'sqlpatch';
