ALTER TABLE releasecomment ADD COLUMN username VARCHAR (255) NOT NULL DEFAULT '';

UPDATE releasecomment SET username = (SELECT username FROM users WHERE users.id = releasecomment.userid);

DELETE FROM users WHERE email = 'sharing@nZEDb.com' AND role = 0;

UPDATE site SET value = '203' WHERE setting = 'sqlpatch';
