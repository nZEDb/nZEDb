UPDATE `tmux` SET `DEHASH` = 0 WHERE `DEHASH` = "FALSE";
UPDATE `tmux` SET `DEHASH` = 3 WHERE `DEHASH` = "TRUE";

UPDATE `site` set `value` = '85' where `setting` = 'sqlpatch';
