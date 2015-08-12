# tgp_change procuedure runs the following on all parts or parts_xx tables in the current database
# ALTER TABLE parts DROP INDEX binaryid;
# ALTER TABLE parts DROP INDEX ix_parts_collection_id;
# ALTER TABLE parts ADD INDEX binaryid (binaryid,partnumber), ADD INDEX ix_parts_collection_id (collection_id,number), MODIFY messageid VARCHAR(255) CHARACTER SET latin1 NOT NULL DEFAULT '';

DROP PROCEDURE IF EXISTS tpg_change;

-- This might be long - patience.

DELIMITER $$
CREATE PROCEDURE tpg_change()
BEGIN
  DECLARE done INT DEFAULT false;
  DECLARE _table CHAR(255);
  DECLARE _stmt VARCHAR(1000);
  DECLARE cur1 CURSOR FOR
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND (TABLE_NAME LIKE "parts\_%" OR TABLE_NAME="parts");
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  OPEN cur1;
    myloop: loop FETCH cur1 INTO _table;
      IF done THEN LEAVE myloop; END IF;
      SET @sql1 := CONCAT("ALTER TABLE ", _table," DROP INDEX binaryid");
      SET @sql2 := CONCAT("ALTER TABLE ", _table," DROP INDEX ix_parts_collection_id");
      SET @sql3 := CONCAT("ALTER TABLE ", _table," ADD INDEX ix_parts_binaryid (binaryid, partnumber), ADD INDEX ix_parts_collection_id (collection_id,number), MODIFY messageid VARCHAR(255) CHARACTER SET latin1 NOT NULL DEFAULT ''");
      PREPARE _stmt FROM @sql1; EXECUTE _stmt; DROP PREPARE _stmt;
      PREPARE _stmt FROM @sql2; EXECUTE _stmt; DROP PREPARE _stmt;
      PREPARE _stmt FROM @sql3; EXECUTE _stmt; DROP PREPARE _stmt;
    END loop;
  CLOSE cur1;
END $$
DELIMITER ;

CALL tpg_change();
DROP PROCEDURE tpg_change;
