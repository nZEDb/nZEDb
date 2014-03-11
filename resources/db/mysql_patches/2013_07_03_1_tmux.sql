UPDATE `tmux` SET `value` = '0' where setting = 'IMPORT' and value = 'FALSE';
UPDATE `tmux` SET `value` = '1' where setting = 'IMPORT' and value = 'TRUE';

UPDATE `site` set `value` = '88' where `setting` = 'sqlpatch';
