UPDATE site SET value = 'http://predb_irc.nzedb.com/predb_irc.php?reqid=[REQUEST_ID]&group=[GROUP_NM]' WHERE setting = 'request_url';

UPDATE `site` set `value` = '130' where `setting` = 'sqlpatch';
