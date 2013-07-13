UPDATE `tmux` SET `value` = '0' where setting = 'POST' and value = 'FALSE';
UPDATE `tmux` SET `value` = '3' where setting = 'POST' and value = 'TRUE';

UPDATE `site` set `value` = '91' where `setting` = 'sqlpatch';
