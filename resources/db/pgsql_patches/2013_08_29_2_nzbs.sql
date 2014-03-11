ALTER TABLE nzbs DROP articlenumber;

UPDATE site SET value = '116' WHERE setting = 'sqlpatch';
