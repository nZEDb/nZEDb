alter table site drop column google_adsense_sidepanel;

alter table site modify `tandc` VARCHAR(1000) NOT NULL;
alter table site modify `title` VARCHAR(255) NOT NULL;
alter table site modify `strapline` VARCHAR(255) NOT NULL;
alter table site modify `email` VARCHAR(255) NOT NULL;
alter table site modify `reqidurl` VARCHAR(255) NOT NULL DEFAULT 'http://allfilled.newznab.com/query.php?t=[GROUP]&reqid=[REQID]';
alter table site modify `latestregexurl` VARCHAR(255) NOT NULL DEFAULT 'http://www.newznab.com/getregex.php';

alter table site add adheader VARCHAR(2000) NULL;
alter table site add adbrowse VARCHAR(2000) NULL;
alter table site add addetail VARCHAR(2000) NULL;