UPDATE `tmux` set `value` = '0' where `value` = "FALSE" and `setting` = 'SEQUENTIAL';
UPDATE `tmux` set `value` = '1' where `value` = "TRUE" and `setting` = 'SEQUENTIAL';

UPDATE `site` set `value` = '109' where `setting` = 'sqlpatch';
