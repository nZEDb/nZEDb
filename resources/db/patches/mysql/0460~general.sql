DROP PROCEDURE IF EXISTS loop_cbpm;

DELIMITER $$

CREATE PROCEDURE loop_cbpm(IN method CHAR(10))
  COMMENT 'Performs tasks on All CBPM tables one by one -- REPAIR/ANALYZE/OPTIMIZE or DROP/TRUNCATE'

  main: BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE tname VARCHAR(255) DEFAULT '';
    DECLARE cur1 CURSOR FOR
      SELECT TABLE_NAME
      FROM information_schema.TABLES
      WHERE
        TABLE_SCHEMA = (SELECT DATABASE())
        AND
        (
          TABLE_NAME LIKE 'collections%'
          OR TABLE_NAME LIKE 'parts%'
          OR TABLE_NAME LIKE 'binaries%'
          OR TABLE_NAME LIKE 'missed%'
        )
      ORDER BY TABLE_NAME ASC;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    IF method NOT IN ('repair', 'analyze', 'optimize', 'drop', 'truncate') THEN LEAVE main; END IF;

    OPEN cur1;
    alter_loop: LOOP FETCH cur1
    INTO tname;
      IF done
        THEN LEAVE alter_loop;
      END IF;
      SET @SQL := CONCAT(method, " TABLE ", tname);
      PREPARE _stmt FROM @SQL;
      EXECUTE _stmt;
      DEALLOCATE PREPARE _stmt;
    END LOOP;
    CLOSE cur1;
  END main;
$$
