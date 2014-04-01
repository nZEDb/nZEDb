ALTER TABLE country RENAME TO countries;

UPDATE `site` set `value` = '187' where `setting` = 'sqlpatch';
