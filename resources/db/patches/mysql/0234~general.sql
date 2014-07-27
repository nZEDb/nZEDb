ALTER TABLE collections CHANGE COLUMN `groupid` `group_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to groups';
ALTER TABLE partrepair CHANGE COLUMN `groupid` `group_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to groups';
ALTER TABLE predb CHANGE COLUMN `groupid` `group_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to groups';
ALTER TABLE releases CHANGE COLUMN `groupid` `group_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to groups';
