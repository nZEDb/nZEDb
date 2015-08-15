-- Adds default values to movieinfo columns.
UPDATE movieinfo SET tmdbid = 0 WHERE tmdbid IS NULL;
ALTER TABLE movieinfo MODIFY tmdbid   INT(10) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE movieinfo MODIFY title    VARCHAR(255)     NOT NULL DEFAULT '';
ALTER TABLE movieinfo MODIFY tagline  VARCHAR(1024)    NOT NULL DEFAULT '';
ALTER TABLE movieinfo MODIFY rating   VARCHAR(4)       NOT NULL DEFAULT '';
ALTER TABLE movieinfo MODIFY plot     VARCHAR(1024)    NOT NULL DEFAULT '';
ALTER TABLE movieinfo MODIFY year     VARCHAR(4)       NOT NULL DEFAULT '';
ALTER TABLE movieinfo MODIFY genre    VARCHAR(64)      NOT NULL DEFAULT '';
ALTER TABLE movieinfo MODIFY type     VARCHAR(32)      NOT NULL DEFAULT '';
ALTER TABLE movieinfo MODIFY director VARCHAR(64)      NOT NULL DEFAULT '';
ALTER TABLE movieinfo MODIFY actors   VARCHAR(2000)    NOT NULL DEFAULT '';
ALTER TABLE movieinfo MODIFY language VARCHAR(64)      NOT NULL DEFAULT '';