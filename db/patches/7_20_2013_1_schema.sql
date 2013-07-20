CREATE INDEX ix_releases_nfostatus ON releases (`nfostatus` ASC) USING HASH;
CREATE INDEX ix_releases_musicinfoID ON releases (`musicinfoID`);
CREATE INDEX ix_releases_consoleinfoID ON releases (`consoleinfoID`);
CREATE INDEX ix_releases_bookinfoID ON releases (`bookinfoID`);
CREATE INDEX ix_releases_haspreview ON releases (`haspreview` ASC) USING HASH;


UPDATE `site` set `value` = '98' where `setting` = 'sqlpatch';
