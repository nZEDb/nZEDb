
DROP TABLE IF EXISTS `menu`;
CREATE TABLE `menu` 
(
`ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`href` VARCHAR(2000) NOT NULL DEFAULT '',
`title` VARCHAR(2000) NOT NULL DEFAULT '',
`tooltip` VARCHAR(2000) NOT NULL DEFAULT '',
`role` INT(11) UNSIGNED NOT NULL,
`ordinal` INT(11) UNSIGNED NOT NULL,
`menueval` VARCHAR(2000) NOT NULL DEFAULT '',
PRIMARY KEY  (`ID`)
) ENGINE=MYISAM DEFAULT CHARSET latin1 COLLATE latin1_general_ci AUTO_INCREMENT=1 ;


INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('search', 'Search', 
	'Search for Nzbs', 1, 10);

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('browse', 'Browse', 
	'Browse for Nzbs', 1, 20);

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('searchraw', 'Raw Search', 
	'Search for individual files', 1, 30);

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('movies', 'Movies', 
	'Browse for Movies', 1, 40);

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('admin', 'Admin', 
	'Admin', 2, 50);

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('cart', 'My Cart', 
	'Your Nzb cart', 1, 60);

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal`, `menueval` )
VALUES ('queue', 'My Queue', 
	'View Your Sabnzbd Queue', 1, 70, '{if $sabintegrated!="true"}-1{/if}');

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('profile', 'Profile', 
	'View your profile', 1, 80);

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('logout', 'Logout', 
	'Logout', 1, 90);

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('login', 'Login', 
	'Login', 0, 100);

INSERT INTO menu (`href`, `title`, `tooltip`, `role`, `ordinal` )
VALUES ('register', 'Register', 
	'Register', 0, 110);
