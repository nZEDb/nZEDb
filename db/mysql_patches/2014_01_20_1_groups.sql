INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.multimedia.erotica.anime', 5, 31457280, 'erotica anime');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.multimedia.erotica.asian', 5, 31457280, 'erotica Asian');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.mp3.audiobooks.scifi-fantasy', NULL, 10485760, 'Audiobooks');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.sounds.audiobooks.scifi-fantasy', NULL, 10485760, 'Audiobooks');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.mp3.abooks', 2, 10485760, 'Audiobooks');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.town.xxx', 5, 10485760, 'XXX videos');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.software', 2, 5048571, 'Software');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.nzbpirates', 5, NULL, 'Misc, mostly Erotica, and foreign movies ');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.town.cine', NULL, 10485760, 'Movies');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.usenet-space-cowboys', 0, 0, 'Misc, mostly German');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.warez.ibm-pc.games', 0, 0, 'misc, mostly games and applications');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.warez.games', 0, 0, 'misc, mostly games and applications');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.e-book.magazines', NULL, 104857, 'magazines, mostly english');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.sounds.anime', 0, 0, 'music from Anime');

UPDATE `site` set `value` = '169' where `setting` = 'sqlpatch';
