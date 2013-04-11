ALTER TABLE `groups`  ADD COLUMN `last_record_postdate` DATETIME NULL DEFAULT NULL AFTER `categoryID`;
ALTER TABLE `groups`  ADD COLUMN `first_record_postdate` DATETIME NULL DEFAULT NULL AFTER `categoryID`;
ALTER TABLE `groups`  CHANGE COLUMN `first_record_postdate` `first_record_postdate` DATETIME NULL DEFAULT NULL AFTER `first_record`;
ALTER TABLE `groups`  CHANGE COLUMN `last_record_postdate` `last_record_postdate` DATETIME NULL DEFAULT NULL AFTER `last_record`;
