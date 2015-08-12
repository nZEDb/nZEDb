# tpg_change procedure runs the following on binaries and binaries_(tpg) tables
#
# CREATE TABLE binaries_tmp LIKE binaries;
# ALTER TABLE binaries_tmp MODIFY `binaryhash` binary(16) NOT NULL DEFAULT '0';
# INSERT INTO binaries_tmp
#   SELECT id, name, collection_id, filenumber, totalparts, currentparts, UNHEX(binaryhash), partcheck, partsize
#   FROM binaries;
# RENAME TABLE binaries TO binaries_old, binaries_tmp TO binaries;
# DROP TABLE binaries_old;

DROP PROCEDURE IF EXISTS tpg_change;

-- This might be long - patience.

DELIMITER $$
CREATE PROCEDURE tpg_change()
BEGIN
  DECLARE done INT DEFAULT false;
  DECLARE _table CHAR(255);
  DECLARE _stmt VARCHAR(1000);
  DECLARE cur1 CURSOR FOR
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = "binaryhash" AND DATA_TYPE <> "binary"
      AND (TABLE_NAME LIKE "binaries\_%" OR TABLE_NAME = "binaries");
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  OPEN cur1;
    myloop: loop FETCH cur1 INTO _table;
      IF done THEN LEAVE myloop; END IF;
      SET @sql0 := CONCAT("DELETE FROM ", _table, " WHERE LENGTH(binaryhash) <> 32");
      SET @sql1 := CONCAT("DROP TABLE IF EXISTS ", _table, "_tmp");
      SET @sql2 := CONCAT("CREATE TABLE ", _table, "_tmp LIKE ", _table);
      SET @sql3 := CONCAT("ALTER TABLE ", _table, "_tmp MODIFY binaryhash BINARY(16) NOT NULL DEFAULT '0'");
      SET @sql4 := CONCAT("INSERT INTO ",
                          _table,
                          "_tmp (id, name, collection_id, filenumber, totalparts, currentparts, binaryhash, partcheck, partsize) (SELECT id, name, collection_id, filenumber, totalparts, currentparts, UNHEX(binaryhash), partcheck, partsize FROM ",
                          _table, ")");
      SET @sql5 := CONCAT("DROP TABLE IF EXISTS ", _table, "_old");
      SET @sql6 := CONCAT("RENAME TABLE ", _table, " TO ", _table, "_old, ", _table, "_tmp TO ", _table);
      SET @sql7 := CONCAT("DROP TABLE IF EXISTS ", _table, "_old");

      PREPARE _stmt FROM @sql0; EXECUTE _stmt; DROP PREPARE _stmt;
      PREPARE _stmt FROM @sql1; EXECUTE _stmt; DROP PREPARE _stmt;
      PREPARE _stmt FROM @sql2; EXECUTE _stmt; DROP PREPARE _stmt;
      PREPARE _stmt FROM @sql3; EXECUTE _stmt; DROP PREPARE _stmt;
      PREPARE _stmt FROM @sql4; EXECUTE _stmt; DROP PREPARE _stmt;
      PREPARE _stmt FROM @sql5; EXECUTE _stmt; DROP PREPARE _stmt;
      PREPARE _stmt FROM @sql6; EXECUTE _stmt; DROP PREPARE _stmt;
      PREPARE _stmt FROM @sql7; EXECUTE _stmt; DROP PREPARE _stmt;
    END loop;
  CLOSE cur1;
END $$
DELIMITER ;

CALL tpg_change();
DROP PROCEDURE tpg_change;
