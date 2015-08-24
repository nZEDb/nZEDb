DROP PROCEDURE IF EXISTS tpg_change;
DROP TABLE IF EXISTS parts_tmp;
DROP TABLE IF EXISTS parts_old;

-- This might be long - patience.

DELIMITER $$
CREATE PROCEDURE tpg_change()
BEGIN
	DECLARE done INT DEFAULT false;
	DECLARE _table CHAR(255);
	DECLARE _engine CHAR(255);
	DECLARE _row_format CHAR(255);
	DECLARE _stmt VARCHAR(1000);
	DECLARE cur1 CURSOR FOR
	SELECT TABLE_NAME, ENGINE, ROW_FORMAT FROM INFORMATION_SCHEMA.TABLES
	WHERE TABLE_SCHEMA = DATABASE()
	AND (TABLE_NAME LIKE "parts\_%" OR TABLE_NAME="parts");
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
	OPEN cur1;
	myloop: loop FETCH cur1 INTO _table, _engine, _row_format;
		IF done THEN LEAVE myloop; END IF;
		SET @sql1 :=
			CONCAT("CREATE TABLE parts_tmp (
				binaryid bigint(20) unsigned NOT NULL DEFAULT '0',
				messageid varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
				number bigint(20) unsigned NOT NULL DEFAULT '0',
				partnumber mediumint(10) unsigned NOT NULL DEFAULT '0',
				size mediumint(20) unsigned NOT NULL DEFAULT '0',
				PRIMARY KEY (binaryid,number)
				) ENGINE=", _engine, " DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=", _row_format
			);
		SET @sql2 := CONCAT("INSERT IGNORE INTO parts_tmp SELECT binaryid, messageid, number, partnumber, size FROM ", _table);
		SET @sql3 := CONCAT("RENAME TABLE ", _table, " TO ", _table, "_old, parts_tmp TO ", _table);
		SET @sql4 := CONCAT("DROP TABLE IF EXISTS ", _table, "_old");
		PREPARE _stmt FROM @sql1; EXECUTE _stmt; DROP PREPARE _stmt;
		PREPARE _stmt FROM @sql2; EXECUTE _stmt; DROP PREPARE _stmt;
		PREPARE _stmt FROM @sql3; EXECUTE _stmt; DROP PREPARE _stmt;
		PREPARE _stmt FROM @sql4; EXECUTE _stmt; DROP PREPARE _stmt;
	END loop;
	CLOSE cur1;
END $$
DELIMITER ;

CALL tpg_change();
DROP PROCEDURE tpg_change;
