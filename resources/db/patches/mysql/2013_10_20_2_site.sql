INSERT IGNORE INTO site (setting, value) VALUE ('nntpproxy', 0);

 UPDATE site SET value = '134' WHERE setting = 'sqlpatch';
