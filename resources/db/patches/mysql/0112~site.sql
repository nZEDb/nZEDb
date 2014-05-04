ALTER TABLE nzbs change nzbs.group nzbs.groupname varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0';

 UPDATE site set value = '112' where setting = 'sqlpatch';
