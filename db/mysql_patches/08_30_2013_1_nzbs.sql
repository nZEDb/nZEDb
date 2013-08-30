DROP TABLE IF EXISTS nzbs;

CREATE TABLE nzbs (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    message_id varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
    groupname varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
    subject varchar(1000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
    collectionhash varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
    filesize bigint(20) unsigned NOT NULL DEFAULT '0',
    partnumber int(10) unsigned NOT NULL DEFAULT '0',
    totalparts int(10) unsigned NOT NULL DEFAULT '0',
    postdate datetime DEFAULT NULL,
    dateadded timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=MyIsam DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

UPDATE site SET value = '117' WHERE setting = 'sqlpatch';
