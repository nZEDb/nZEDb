INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.multimedia.erotica.anime', NULL, NULL, 'erotica anime');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.multimedia.erotica.asian', NULL, NULL, 'erotica Asian');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.mp3.audiobooks.scifi-fantasy', NULL, NULL, 'Audiobooks');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.sounds.audiobooks.scifi-fantasy', NULL, NULL, 'Audiobooks');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.mp3.abooks', NULL, NULL, 'Audiobooks');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.town.xxx', NULL, NULL, 'XXX videos');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.software', NULL, NULL, 'Software');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.nzbpirates', NULL, NULL, 'Misc, mostly Erotica, and foreign movies ');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.town.cine', NULL, NULL, 'Movies');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.usenet-space-cowboys', NULL, NULL, 'Misc, mostly German');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.warez.ibm-pc.games', NULL, NULL, 'misc, mostly games and applications');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.warez.games', NULL, NULL, 'misc, mostly games and applications');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.e-book.magazines', NULL, NULL, 'magazines, mostly english');
INSERT IGNORE INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.sounds.anime', NULL, NULL, 'music from Anime');

UPDATE `site` set `value` = '169' where `setting` = 'sqlpatch';
