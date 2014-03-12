UPDATE releases SET bitwise = ((bitwise & ~256)|256) WHERE nzbstatus = 1;
UPDATE releases SET bitwise = ((bitwise & ~256)|0) WHERE nzbstatus != 1;
ALTER TABLE releases DROP COLUMN nzbstatus;

UPDATE site SET value = '151' WHERE setting = 'sqlpatch';
