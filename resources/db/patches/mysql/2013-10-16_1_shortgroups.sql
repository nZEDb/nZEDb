DROP TABLE IF EXISTS shortgroups;
CREATE TABLE shortgroups LIKE allgroups;

UPDATE `site` set `value` = '131' where `setting` = 'sqlpatch';
