ALTER TABLE gamesinfo ADD COLUMN backdrop TINYINT(1) DEFAULT 0 AFTER cover;
ALTER TABLE gamesinfo ADD COLUMN trailer VARCHAR(1000) DEFAULT '' AFTER backdrop;
ALTER TABLE gamesinfo ADD COLUMN classused VARCHAR(10) DEFAULT 'steam' AFTER trailer;
UPDATE gamesinfo SET classused = 'gb';
