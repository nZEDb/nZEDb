CREATE INDEX ix_category_status ON category(status);
UPDATE `site` set `value` = '48' where `setting` = 'sqlpatch';
