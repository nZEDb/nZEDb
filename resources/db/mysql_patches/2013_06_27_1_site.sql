INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('postthreadsamazon', '1'), ('postthreadsnon', '1');
INSERT IGNORE INTO `tmux` (`setting`, `value`) VALUE ('POST_AMAZON', 'FALSE'), ('POST_NON', 'FALSE'),  ('POST_TIMER_AMAZON', '30'), ('POST_TIMER_NON', '30');

UPDATE `site` set `value` = '82' where `setting` = 'sqlpatch';
