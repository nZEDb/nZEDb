INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.astronomy','This group contains mostly movies.', NULL, NULL);

UPDATE `site` set `value` = '107' where `setting` = 'sqlpatch';
