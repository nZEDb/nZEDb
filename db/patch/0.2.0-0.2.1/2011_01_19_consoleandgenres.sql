RENAME TABLE `musicgenre` TO `genres` ;

ALTER TABLE  `genres` ADD  `type` INT( 4 ) NULL DEFAULT NULL;

UPDATE `genres` SET `type` = 3000;

ALTER TABLE  `musicinfo` CHANGE  `musicgenreID`  `genreID` INT( 10 ) NULL DEFAULT NULL;


ALTER TABLE  `consoleinfo` ADD  `genreID` INT( 10 ) NULL DEFAULT NULL AFTER  `publisher`;

ALTER TABLE  `consoleinfo` ADD  `esrb` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `genreID`;