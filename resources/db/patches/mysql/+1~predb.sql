# Updating PreDb table to use UTC timestamp for created and updated dates.
ALTER TABLE predb
  ADD COLUMN created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
COMMENT 'Unix time of when the pre was created, or first noted by the system' AFTER category,
  ADD COLUMN updated TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP
COMMENT 'Unix time of when the entry was last updated' AFTER created;

UPDATE predb SET created = predate;

#Dropping the predate column which is now accessed as 'created'
ALTER TABLE predb DROP COLUMN predate;

#Altering Predb_Imports table
ALTER TABLE predb_imports
  ADD COLUMN created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
COMMENT 'Unix time of when the pre was created, or first noted by the system'
  AFTER category,
  ADD COLUMN updated TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP
COMMENT 'Unix time of when the entry was last updated'
  AFTER created;
