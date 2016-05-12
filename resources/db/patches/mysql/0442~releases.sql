# NOTE: Add leftguid column to releases and associated index for threaded lookups

ALTER TABLE releases
  ADD COLUMN leftguid CHAR(1) NOT NULL COMMENT 'The first letter of the release guid' AFTER guid,
  ADD INDEX ix_releases_leftguid (leftguid ASC, predb_id);

# NOTE: Populate existing releases with their leftmost guid character
UPDATE releases SET leftguid = LEFT(guid, 1);