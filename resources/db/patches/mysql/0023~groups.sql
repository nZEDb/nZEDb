INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.tun', 'This group contains various.', NULL, NULL);

UPDATE `site` set `value` = '23' where `setting` = 'sqlpatch';
