# This patch will add the proc_uid column to the releases table

ALTER TABLE releases
  ADD COLUMN proc_uid TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Has the release been UID processed?' AFTER proc_files;
