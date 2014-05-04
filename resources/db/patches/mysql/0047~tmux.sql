INSERT IGNORE INTO `tmux` (`setting`, `value`) VALUE ('SORTER', 'FALSE'), ('SORTER_TIMER', '30');

UPDATE `site` set `value` = '47' where `setting` = 'sqlpatch';
