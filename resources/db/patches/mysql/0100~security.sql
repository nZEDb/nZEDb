DROP TABLE IF EXISTS `logging`;
CREATE TABLE `logging` (
  `time` datetime DEFAULT NULL,
  `username` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `host` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT IGNORE INTO `site` (`setting`, `value`) VALUES ('loggingopt', 2);
INSERT IGNORE INTO `site` (`setting`, `value`) VALUES ('logfile', '/var/www/nZEDb/failed-login.log');

UPDATE `site` set `value` = '100' where `setting` = 'sqlpatch';
