/* Drop the adddate index */
ALTER TABLE predb DROP INDEX ix_predb_adddate;

/* Drop the adddate column */
ALTER TABLE predb DROP COLUMN adddate;

/* Use the site to keep the last pre time (unixtime) */
INSERT INTO site (setting, value) VALUES ('lastpretime', '0');

UPDATE site SET value = '193' WHERE setting = 'sqlpatch';