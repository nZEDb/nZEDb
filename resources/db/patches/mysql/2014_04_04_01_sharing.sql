DROP TABLE IF EXISTS sharing_sites;
CREATE TABLE sharing_sites (
	id             INT(11) UNSIGNED   NOT NULL AUTO_INCREMENT,
	site_name      VARCHAR(255)       NOT NULL DEFAULT '',
	site_guid      VARCHAR(40)        NOT NULL DEFAULT '',
	last_time      DATETIME           DEFAULT NULL,
	first_time     DATETIME           DEFAULT NULL,
	enabled        TINYINT(1)         NOT NULL DEFAULT '0',
	comments       MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY    (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS sharing;
CREATE TABLE sharing (
	site_guid      VARCHAR(40)        NOT NULL DEFAULT '',
	site_name      VARCHAR(255)       NOT NULL DEFAULT '',
	enabled        TINYINT(1)         NOT NULL DEFAULT '0',
	posting        TINYINT(1)         NOT NULL DEFAULT '0',
	fetching       TINYINT(1)         NOT NULL DEFAULT '1',
	auto_enable    TINYINT(1)         NOT NULL DEFAULT '1',
	hide_users     TINYINT(1)         NOT NULL DEFAULT '1',
	last_article   BIGINT UNSIGNED    NOT NULL DEFAULT '0',
	max_push       MEDIUMINT UNSIGNED NOT NULL DEFAULT '40',
	max_pull       INT UNSIGNED       NOT NULL DEFAULT '150',
	PRIMARY KEY    (site_guid)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ;

ALTER TABLE releasecomment ADD COLUMN shared   TINYINT(1)  NOT NULL DEFAULT '0';
ALTER TABLE releasecomment ADD COLUMN shareid  VARCHAR(40) NOT NULL DEFAULT '';
ALTER TABLE releasecomment ADD COLUMN nzb_guid VARCHAR(32) NOT NULL DEFAULT '';

UPDATE site SET value = '198' WHERE setting = 'sqlpatch';
