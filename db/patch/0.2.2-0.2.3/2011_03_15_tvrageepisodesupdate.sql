ALTER TABLE `tvrageepisodes` CHANGE `tvrageID` `rageID` INT( 11 ) UNSIGNED NOT NULL;
ALTER TABLE `tvrageepisodes` CHANGE `title` `eptitle` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ;
ALTER TABLE `tvrageepisodes` ADD `showtitle` VARCHAR( 255 ) NULL AFTER `rageID` ;