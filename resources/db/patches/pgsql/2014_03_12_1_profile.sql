ALTER TABLE users ADD "style" character varying(255);

UPDATE site set value = '182' where setting = 'sqlpatch';