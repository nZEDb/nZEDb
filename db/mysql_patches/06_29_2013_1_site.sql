UPDATE `tmux` SET value = 0 WHERE setting = 'DEHASH' AND value = "FALSE";
UPDATE `tmux` SET value = 3 WHERE setting = 'DEHASH' AND value = "TRUE";

UPDATE `site` set `value` = '85' where `setting` = 'sqlpatch';
