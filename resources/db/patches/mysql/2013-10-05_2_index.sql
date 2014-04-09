CREATE INDEX ix_releases_mergedreleases on releases(dehashstatus, relnamestatus, passwordstatus);

UPDATE `site` set `value` = '129' where `setting` = 'sqlpatch';
