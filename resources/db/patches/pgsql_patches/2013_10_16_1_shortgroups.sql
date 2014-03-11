DROP TABLE IF EXISTS shortgroups;
CREATE TABLE shortgroups (LIKE allgroups INCLUDING ALL);

UPDATE `site` set `value` = '131' where `setting` = 'sqlpatch';
