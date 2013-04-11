alter table site add `latestregexurl` VARCHAR(1000) NOT NULL DEFAULT 'http://www.newznab.com/latestregex.sql' ;
alter table site add `latestregexrevision` INT NOT NULL DEFAULT 0 ;