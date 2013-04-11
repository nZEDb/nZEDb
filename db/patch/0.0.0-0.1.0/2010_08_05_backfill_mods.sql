ALTER TABLE `groups`  ADD COLUMN `backfill_target` INT(4) NOT NULL DEFAULT '0' AFTER `categoryID`;
UPDATE `groups` SET `backfill_target`=0 WHERE 1;
