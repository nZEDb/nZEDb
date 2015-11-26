# This patch will eliminate the useless id column from release_nfos
# This should improve query times for nfo operations across the board
# and save a little hdd/ram space

# Remove auto_increment, drop and recreate the primary key and drop
# now duplicate index
ALTER TABLE release_nfos
  MODIFY COLUMN id INT(11) UNSIGNED NOT NULL,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (releaseid),
  DROP INDEX ix_releasenfo_releaseid;

# Drop the id column
ALTER TABLE release_nfos DROP COLUMN id;
