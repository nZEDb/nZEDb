UPDATE settings SET value = 'http://reqid.nzedb.com/index.php?reqid=[REQUEST_ID]&group=[GROUP_NM]' WHERE setting = 'request_url';

UPDATE settings SET value = '218' WHERE setting = 'sqlpatch';