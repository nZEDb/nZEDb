UPDATE releases SET categoryid = 7010 WHERE categoryid IS NULL;
ALTER TABLE releases MODIFY categoryid INT(11) NOT NULL DEFAULT '7010';

UPDATE `site` SET value = '164' WHERE setting = 'sqlpatch';
