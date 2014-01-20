BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.multimedia.erotica.anime', NULL, NULL, 'erotica anime');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.multimedia.erotica.asian', NULL, NULL, 'erotica Asian');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.mp3.audiobooks.scifi-fantasy', NULL, NULL, 'Audiobooks');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.sounds.audiobooks.scifi-fantasy', NULL, NULL, 'Audiobooks');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.mp3.abooks', NULL, NULL, 'Audiobooks');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.town.xxx', NULL, NULL, 'XXX videos');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.software', NULL, NULL, 'Software');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.nzbpirates', NULL, NULL, 'Misc, mostly Erotica, and foreign movies ');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.town.cine', NULL, NULL, 'Movies');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.usenet-space-cowboys', NULL, NULL, 'Misc, mostly German');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.warez.ibm-pc.games', NULL, NULL, 'misc, mostly games and applications');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.warez.games', NULL, NULL, 'misc, mostly games and applications');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.e-book.magazines', NULL, 104857, 'magazines, mostly english');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;
BEGIN
	INSERT INTO `groups` (`name`, `minfilestoformrelease`, `minsizetoformrelease`, `description`) VALUES ('alt.binaries.sounds.anime', NULL, NULL, 'music from Anime');
  EXCEPTION WHEN unique_violation THEN
    -- Ignore duplicate inserts.
END;

UPDATE `site` set `value` = '169' where `setting` = 'sqlpatch';
