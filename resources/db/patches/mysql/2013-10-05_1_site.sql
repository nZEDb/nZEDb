UPDATE site SET value = 'http://predbirc.nzedb.com/predb_irc.php?reqid=[REQUEST_ID]&group=[GROUP_NM]' WHERE setting = 'request_url';

UPDATE `site` set `value` = '128' where `setting` = 'sqlpatch';
