ALTER TABLE `groups`  CHANGE COLUMN `backfill_target` `backfill_target` INT(4) NOT NULL DEFAULT '1' AFTER `name`;
