INSERT IGNORE INTO `tmux` (`setting`, `value`) VALUES ('DEHASH','FALSE'), ('DEHASH_TIMER','30');

UPDATE `site` set `value` = '71' where `setting` = 'sqlpatch';
