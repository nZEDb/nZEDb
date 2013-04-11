ALTER TABLE  `movieinfo` ADD  `tagline` VARCHAR( 255 ) NOT NULL AFTER  `rating`,
ADD  `director` VARCHAR( 64 ) NOT NULL AFTER  `genre` ,
ADD  `actors` VARCHAR( 255 ) NOT NULL AFTER  `director` ,
ADD  `language` VARCHAR( 64 ) NOT NULL AFTER  `actors`;