ALTER TABLE gamesinfo DROP platform;
ALTER TABLE gamesinfo ADD COLUMN backdrop TINYINT(1) DEFAULT 0 AFTER cover;
ALTER TABLE gamesinfo ADD COLUMN trailer VARCHAR(1000) DEFAULT 0 AFTER backdrop;
ALTER TABLE gamesinfo ADD COLUMN classused VARCHAR(10) DEFAULT 'gb' AFTER trailer;
UPDATE gamesinfo SET classused = 'gb';
