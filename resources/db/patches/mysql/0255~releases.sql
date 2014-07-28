UPDATE releases SET gamesinfo_id = 0 WHERE gamesinfo_id IS NULL;
ALTER TABLE releases MODIFY COLUMN gamesinfo_id INT(10) SIGNED NOT NULL DEFAULT '0';
UPDATE releases SET xxxinfo_id = 0 WHERE xxxinfo_id IS NULL;
ALTER TABLE releases MODIFY COLUMN xxxinfo_id INT(10) SIGNED NOT NULL DEFAULT '0';
