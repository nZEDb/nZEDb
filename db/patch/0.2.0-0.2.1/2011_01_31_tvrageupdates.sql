ALTER TABLE  `tvrage` ADD  `genre` VARCHAR( 64 ) NULL DEFAULT NULL AFTER  `description`;

ALTER TABLE  `tvrage` ADD  `country` VARCHAR( 2 ) NULL DEFAULT NULL AFTER  `genre`;