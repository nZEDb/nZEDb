UPDATE `tmux` SET `IMPORT` = 0 where `IMPORT` = 'FALSE';
UPDATE `tmux` SET `IMPORT` = 1 where `IMPORT` = 'TRUE';

UPDATE `site` set `value` = '88' where `setting` = 'sqlpatch';

