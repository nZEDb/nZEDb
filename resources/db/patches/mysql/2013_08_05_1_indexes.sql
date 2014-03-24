CREATE INDEX ix_releases_searchname ON releases(`searchname`);

CREATE INDEX ix_partrepair_attempts ON partrepair(`attempts`);

CREATE INDEX ix_binaryblacklist_status ON binaryblacklist(`status`);

CREATE INDEX ix_releases_groupid ON releases(`groupID`);

CREATE INDEX ix_groups_id ON groups(`ID`);

CREATE INDEX ix_movieinfo_title ON movieinfo(`title`);

ALTER TABLE `tvrage` ADD UNIQUE KEY `rageID` (`rageID`, `releasetitle`);

UPDATE `site` set `value` = '104' where `setting` = 'sqlpatch';
