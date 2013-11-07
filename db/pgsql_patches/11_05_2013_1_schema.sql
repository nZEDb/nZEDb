CREATE INDEX ix_releases_nzb_guid ON releases (nzbstatus, nzb_guid);

UPDATE site SET value = '136' WHERE setting = 'sqlpatch';
