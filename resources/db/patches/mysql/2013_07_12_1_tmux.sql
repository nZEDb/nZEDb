UPDATE `tmux` SET `value` = '0' where setting = 'POST' and value = 'FALSE';
UPDATE `tmux` SET `value` = '3' where setting = 'POST' and value = 'TRUE';
UPDATE `tmux` SET `value` = '0' where setting = 'FIX_CRAP' and value = 'FALSE';
UPDATE `tmux` SET `value` = '1' where setting = 'FIX_CRAP' and value = 'TRUE';

UPDATE `site` set `value` = '95' where `setting` = 'sqlpatch';
