DELETE from `site` where `setting` = 'predbhashcheck';

UPDATE `site` set `value` = '64' where `setting` = 'sqlpatch';
