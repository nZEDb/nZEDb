DROP INDEX ix_releases_name_searchname_ft ON releases;
DROP INDEX ix_releases_guid ON releases;
DROP INDEX ix_releases_adddate ON releases;
DROP INDEX ix_releases_categoryid ON releases;
DROP INDEX ix_releases_rageid ON releases;
DROP INDEX ix_releases_imdbid ON releases;
DROP INDEX ix_releases_name ON releases;
DROP INDEX ix_releases_searchname ON releases;
DROP INDEX ix_releases_groupid ON releases;
DROP INDEX ix_releases_dehashstatus ON releases;
DROP INDEX ix_releases_preid ON releases;
DROP INDEX ix_releases_passwordstatus ON releases;
DROP INDEX ix_releases_reqidstatus ON releases;
DROP INDEX ix_releases_nfostatus ON releases;
DROP INDEX ix_releases_musicinfoid ON releases;
DROP INDEX ix_releases_consoleinfoid ON releases;
DROP INDEX ix_releases_bookinfoid ON releases;
DROP INDEX ix_releases_haspreview ON releases;
DROP INDEX ix_releases_status ON releases;
DROP INDEX ix_releases_postdate_searchname ON releases;
DROP INDEX ix_releases_postdate_name ON releases;
DROP INDEX ix_releases_nzb_guid ON releases;
DROP INDEX ix_releases_preid_searchname ON releases;
DROP INDEX idx_releases_multi_name_fromname_size ON releases;

ALTER TABLE releases MODIFY id INT(11) UNSIGNED NOT NULL;
ALTER TABLE releases DROP PRIMARY KEY;
ALTER TABLE releases ADD PRIMARY KEY (id, categoryid);
ALTER TABLE releases MODIFY id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE releases PARTITION BY RANGE (categoryid) (
	PARTITION unused VALUES LESS THAN (1000),
	PARTITION console VALUES LESS THAN (2000),
	PARTITION movies VALUES LESS THAN (3000),
	PARTITION audio VALUES LESS THAN (4000),
	PARTITION pc VALUES LESS THAN (5000),
	PARTITION tv VALUES LESS THAN (6000),
	PARTITION xxx VALUES LESS THAN (7000),
	PARTITION misc VALUES LESS THAN (8000),
	PARTITION books VALUES LESS THAN (9000) );

ALTER TABLE releases ADD INDEX ix_releases_adddate (adddate);
ALTER TABLE releases ADD INDEX ix_releases_rageid (rageid);
ALTER TABLE releases ADD INDEX ix_releases_imdbid (imdbid);
ALTER TABLE releases ADD INDEX ix_releases_guid (guid);
ALTER TABLE releases ADD INDEX ix_releases_name (name);
ALTER TABLE releases ADD INDEX ix_releases_groupid (groupid);
ALTER TABLE releases ADD INDEX ix_releases_dehashstatus (dehashstatus);
ALTER TABLE releases ADD INDEX ix_releases_reqidstatus (reqidstatus);
ALTER TABLE releases ADD INDEX ix_releases_nfostatus (nfostatus);
ALTER TABLE releases ADD INDEX ix_releases_musicinfoid (musicinfoid);
ALTER TABLE releases ADD INDEX ix_releases_consoleinfoid (consoleinfoid);
ALTER TABLE releases ADD INDEX ix_releases_bookinfoid (bookinfoid);
ALTER TABLE releases ADD INDEX ix_releases_haspreview_passwordstatus (haspreview, passwordstatus);
ALTER TABLE releases ADD INDEX ix_releases_status (nzbstatus, iscategorized, isrenamed, nfostatus, ishashed, isrequestid, passwordstatus, dehashstatus, reqidstatus, musicinfoid, consoleinfoid, bookinfoid, haspreview, categoryid, imdbid, rageid);
ALTER TABLE releases ADD INDEX ix_releases_postdate_searchname (postdate, searchname);
ALTER TABLE releases ADD INDEX ix_releases_nzb_guid (nzb_guid);
ALTER TABLE releases ADD INDEX ix_releases_preid_searchname (preid, searchname);

UPDATE site set value = '183' where setting = 'sqlpatch';
