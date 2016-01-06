# This patch will add the proc_srr column to the releases table

ALTER TABLE releases
  ADD COLUMN proc_srr TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Has the release been SRR processed?' AFTER proc_files;