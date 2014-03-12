CREATE INDEX ix_releases_relnamestatus ON releases(relnamestatus);
CREATE INDEX ix_releases_passwordstatus ON releases(passwordstatus);
UPDATE `site` set `value` = '49' where `setting` = 'sqlpatch';
