# This patch will eliminate the useless id column from dnzb_failures
# This should improve query times for failed releases operations across the board
# and save a little hdd/ram space

# Remove auto_increment, drop and recreate the primary key , rename releaseid column and drop
# index
ALTER TABLE dnzb_failures
  MODIFY COLUMN id INT(11) UNSIGNED NOT NULL,
  CHANGE COLUMN releaseid release_id INT(11) UNSIGNED  NOT NULL AFTER id,
  DROP INDEX ux_dnzb_failures,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (release_id, userid),
  DROP INDEX ix_dnzb_releaseid;

# Drop the id and guid columns
ALTER TABLE dnzb_failures
  DROP COLUMN id,
  DROP COLUMN guid;
