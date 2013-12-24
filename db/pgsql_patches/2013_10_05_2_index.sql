DROP INDEX IF EXISTS "releases_mergedreleases" CASCADE;

CREATE INDEX releases_mergedreleases on releases(dehashstatus, relnamestatus, passwordstatus);


UPDATE site SET value = '129' WHERE setting = 'sqlpatch';
