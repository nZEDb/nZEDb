UPDATE releases SET preid = NULL, searchname = name, bitwise = ((bitwise & ~5)|0) WHERE LENGTH(searchname) <= 15 AND preid IS NOT NULL;
DELETE FROM predb WHERE LENGTH(title) <= 15;

UPDATE `site` set `value` = '170' where `setting` = 'sqlpatch';
