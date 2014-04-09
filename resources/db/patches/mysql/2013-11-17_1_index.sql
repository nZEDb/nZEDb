CREATE INDEX ix_partrepair_groupid_attempts ON partrepair (groupid,attempts);
CREATE INDEX ix_partrepair_numberid_groupid_attempts ON partrepair (numberid,groupid,attempts);

UPDATE site SET value = '148' WHERE setting = 'sqlpatch';
