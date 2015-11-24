# improve performance of CBP cleanup of OLD collections
# nzedb/processing/ProcessReleases.php L1639-L1648
DROP PROCEDURE IF EXISTS tpg_change;

DELIMITER $$
CREATE PROCEDURE tpg_change()
BEGIN
  DECLARE done INT DEFAULT false;
  DECLARE _table CHAR(255);
  DECLARE _stmt VARCHAR(1000);
  DECLARE cur1 CURSOR FOR
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND (TABLE_NAME LIKE "collections\_%" OR TABLE_NAME="collections");
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  OPEN cur1;
    myloop: loop FETCH cur1 INTO _table;
      IF done THEN LEAVE myloop; END IF;
      SET @sql1 := CONCAT("ALTER TABLE ", _table," ADD INDEX ix_collection_added(added)");
      PREPARE _stmt FROM @sql1;
      EXECUTE _stmt;
      DROP PREPARE _stmt;
    END loop;
  CLOSE cur1;
END $$
DELIMITER ;

CALL tpg_change();
DROP PROCEDURE tpg_change;
