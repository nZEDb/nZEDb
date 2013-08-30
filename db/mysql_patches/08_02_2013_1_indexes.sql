CREATE INDEX ix_binaryblacklist_groupname ON binaryblacklist(`groupname`);

CREATE INDEX ix_category_parentid ON category(`parentID`);

CREATE INDEX ix_nzbs_partnumber ON nzbs(`partnumber`);

CREATE INDEX ix_nzbs_collectionhash ON nzbs(`collectionhash`);

CREATE INDEX ix_tvrage_releasetitle ON tvrage (`releasetitle`);

UPDATE `site` set `value` = '101' where `setting` = 'sqlpatch';
