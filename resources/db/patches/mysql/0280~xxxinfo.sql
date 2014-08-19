ALTER TABLE xxxinfo MODIFY COLUMN classused varchar(4) DEFAULT 'ade';
UPDATE xxxinfo SET classused = 'aebn' WHERE classused = 'aeb';
