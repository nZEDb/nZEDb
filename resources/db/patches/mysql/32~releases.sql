CREATE INDEX ix_release_name ON releases(`name`);

UPDATE `site` set `value` = '32' where `setting` = 'sqlpatch';
