INSERT IGNORE INTO `tmux` (`setting`, `value`) VALUE ('PATCHDB', 'FALSE'),('PATCHDB_TIMER', '21600');

UPDATE `site` set `value` = '59' where `setting` = 'sqlpatch';
