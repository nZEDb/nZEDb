ALTER TABLE predb DROP releaseid;
ALTER TABLE releases DROP relstatus;

UPDATE site SET value = '114' WHERE setting = 'sqlpatch';
