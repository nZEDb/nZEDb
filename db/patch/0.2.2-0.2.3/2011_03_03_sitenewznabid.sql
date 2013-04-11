ALTER TABLE site ADD newznabID VARCHAR(50) NULL;
update site set latestregexurl = 'http://www.newznab.com/getregex.php' where latestregexurl = 'http://www.newznab.com/latestregex.sql';