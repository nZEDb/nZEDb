INSERT IGNORE INTO `tmux` (`setting`, `value`) VALUE ('BACKFILL_DAYS', '1');

UPDATE `site` set `value` = '79' where `setting` = 'sqlpatch';
