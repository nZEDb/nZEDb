UPDATE `tmux` SET `value` = '0' WHERE `setting` = 'BACKFILL';
DELETE FROM `tmux` WHERE `setting` = 'BACKFILL_DELAY';
DELETE FROM `tmux` WHERE `setting` = 'BACKFILL_TYPE';

UPDATE `site` set `value` = '70' where `setting` = 'sqlpatch';
