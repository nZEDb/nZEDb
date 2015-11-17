# Create the temp table
DROP TABLE IF EXISTS release_files_new;
CREATE TABLE release_files_new (
  releaseid int(11) unsigned NOT NULL,
  name varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  size bigint(20) unsigned NOT NULL DEFAULT '0',
  ishashed tinyint(1) NOT NULL DEFAULT '0',
  createddate datetime DEFAULT NULL,
  passworded tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (releaseid, name),
  KEY ix_releasefiles_ishashed (ishashed)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

# Copy the data from the old table into the new and remove duplicates
INSERT IGNORE INTO release_files_new (releaseid, name, size, ishashed, createddate, passworded)
  (SELECT releaseid, name, size, ishashed, createddate, passworded FROM release_files);

# Rename the old table
ALTER TABLE release_files RENAME release_files_old;

# Rename the new table
ALTER TABLE release_files_new RENAME release_files;

# Drop the old table
DROP TABLE release_files_old;

# Analyze Table to refresh the indexes
ANALYZE TABLE release_files;