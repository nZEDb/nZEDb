ALTER TABLE users DROP COLUMN nzbgetapikey;
/* NZBGet has no API Key. */

UPDATE users SET queuetype = 1;
/* Set queue type to 1 (Sabnzbd) */

UPDATE site SET value = '195' WHERE setting = 'sqlpatch';