ALTER TABLE sharing ADD COLUMN start_position TINYINT(1) NOT NULL DEFAULT '0';

UPDATE site SET value = '201' WHERE setting = 'sqlpatch';
