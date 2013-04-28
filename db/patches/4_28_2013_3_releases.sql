CREATE INDEX ix_release_name ON releases('name');

UPDATE `site` set `value` = '11' where `setting` = 'sqlpatch';
