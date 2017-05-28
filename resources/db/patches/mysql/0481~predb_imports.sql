#Altering Predb_Imports table
ALTER TABLE predb_imports
  ADD COLUMN created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Unix time of when the  pre was created, or first noted by the system' AFTER category,
  ADD COLUMN updated TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Unix time of when the entry was last updated' AFTER created,
  DROP COLUMN predate;
