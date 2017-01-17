# Changing the binaries table to use collections_id instead of collection_id.
#
DROP PROCEDURE IF EXISTS change_binaries;
DELIMITER $$
CREATE PROCEDURE change_binaries()
  BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE _table CHAR(255);
    DECLARE _stmt VARCHAR(1000);
    DECLARE cur1 CURSOR FOR
      SELECT TABLE_NAME
      FROM INFORMATION_SCHEMA.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
            AND (TABLE_NAME LIKE "binaries\_%" OR TABLE_NAME = "binaries");
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    OPEN cur1;
    myloop: LOOP FETCH cur1
    INTO _table;
      IF done
      THEN LEAVE myloop; END IF;
      SET @sql1 := CONCAT("ALTER TABLE ", _table,
                          " CHANGE COLUMN collection_id collections_id INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to collections(_xxx).id'");
      PREPARE _stmt FROM @sql1;
      EXECUTE _stmt;
      DROP PREPARE _stmt;
    END LOOP;
    CLOSE cur1;
  END $$
DELIMITER ;

CALL change_binaries();
DROP PROCEDURE change_binaries;
