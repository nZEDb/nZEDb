# - First we drop all secondary indexes on releases to enforce schema compliance
# - This stored procedure will loop through the indexes and drop them one by one
# - Creating additional indexes is up to the end user however issues caused by
#   indexes on the releases table not listed in schema will not be supported

DROP PROCEDURE IF EXISTS releases_index_drop;

DELIMITER $$

CREATE PROCEDURE releases_index_drop()
BEGIN
	DECLARE _index VARCHAR(1000);
	DECLARE _stmt VARCHAR(1000);

	SET _index = (SELECT GROUP_CONCAT(DISTINCT 'DROP INDEX ', INDEX_NAME SEPARATOR ', ')
		FROM INFORMATION_SCHEMA.STATISTICS
		WHERE TABLE_SCHEMA = DATABASE()
		AND TABLE_NAME = 'releases'
		AND INDEX_NAME != 'PRIMARY');
	SET @sql := CONCAT('ALTER TABLE releases ', _index);
	PREPARE _stmt FROM @sql;
	EXECUTE _stmt;
	DROP PREPARE _stmt;

END $$

DELIMITER ;

CALL releases_index_drop();

DROP PROCEDURE releases_index_drop;

# - Now create the new indexes
# - Note: These indexes are good for all datasets, but REALLY improve site and processing performance
#   on larger databases so it will be much more scalable.  It also reduces index space consumption
#   by about 40%
# - These are by no means comprehensive - there will be additions provided the general memory usage
#   and insert performance consensus across the userbase is acceptable

ALTER TABLE releases
	ADD INDEX ix_releases_name (name),
	ADD INDEX ix_releases_group_id (group_id,passwordstatus),
	ADD INDEX ix_releases_postdate_searchname (postdate,searchname),
	ADD INDEX ix_releases_guid (guid),
	ADD INDEX ix_releases_nzb_guid (nzb_guid),
	ADD INDEX ix_releases_rageid (rageid),
	ADD INDEX ix_releases_imdbid (imdbid),
	ADD INDEX ix_releases_xxxinfo_id (xxxinfo_id),
	ADD INDEX ix_releases_musicinfoid (musicinfoid,passwordstatus),
	ADD INDEX ix_releases_consoleinfoid (consoleinfoid),
	ADD INDEX ix_releases_gamesinfo_id (gamesinfo_id),
	ADD INDEX ix_releases_bookinfoid (bookinfoid),
	ADD INDEX ix_releases_anidbid (anidbid),
	ADD INDEX ix_releases_preid_searchname (preid,searchname),
	ADD INDEX ix_releases_haspreview_passwordstatus (haspreview,passwordstatus),
	ADD INDEX ix_releases_passwordstatus (passwordstatus),
	ADD INDEX ix_releases_nfostatus (nfostatus,size),
	ADD INDEX ix_releases_dehashstatus (dehashstatus,ishashed),
	ADD INDEX ix_releases_reqidstatus (adddate,reqidstatus,isrequestid);

# - Now ANALYZE releases - while this is not important for InnoDB, it is DIRE for TokuDB
#   to (at least on Percona) restore index cardinality - on TokuDB this takes no small amount
#   of time due to fractal tree BE PATIENT

ANALYZE TABLE releases;
