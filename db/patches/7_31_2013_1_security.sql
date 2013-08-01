CREATE TABLE IF NOT EXISTS `logging` (
  `time` datetime DEFAULT NULL,
  `username` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `host` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT IGNORE INTO `site` (setting, value, updatedate) values (loggingopt, 2, now());
INSERT IGNORE INTO `site` (setting, value, updatedate) values (logfile, '/var/www/nZEDb/failed-login.log', now());

UPDATE `site` set `value` = '100' where `setting` = 'sqlpatch';
