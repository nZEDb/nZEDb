ALTER TABLE releasecomment ADD COLUMN siteid VARCHAR(40) NOT NULL DEFAULT '';

UPDATE site SET value = '200' WHERE setting = 'sqlpatch';
