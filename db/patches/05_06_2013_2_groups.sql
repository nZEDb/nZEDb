INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.cats', 'This group contains mostly TV.', NULL, NULL);

INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.chello', 'This group contains mostly TV.', NULL, NULL);

INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.downunder', 'This group contains mostly TV.', NULL, NULL);

INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.font', 'This group contains mostly TV.', NULL, NULL);

INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.lou', 'This group contains mostly german TV.', NULL, NULL);

INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.milo', 'This group contains mostly TV, some german.', NULL, NULL);

INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.mojo', 'This group contains mostly TV.', NULL, NULL);

INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.tatu', 'This group contains mostly French TV.', NULL, NULL);

INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.ucc', 'This group contains mostly TV.', NULL, NULL);

INSERT IGNORE INTO `groups` (`name`, `description`, `minfilestoformrelease`, `minsizetoformrelease`) VALUES ('alt.binaries.ufg', 'This group contains mostly TV.', NULL, NULL);

UPDATE `site` set `value` = '24' where `setting` = 'sqlpatch';
