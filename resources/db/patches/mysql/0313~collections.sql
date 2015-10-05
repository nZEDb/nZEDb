DROP PROCEDURE IF EXISTS update_collections;

DELIMITER $$

CREATE PROCEDURE update_collections() LANGUAGE SQL DETERMINISTIC CONTAINS SQL READS SQL DATA COMMENT 'Adds column definition "noise" for collections% tables' BEGIN DECLARE done INT DEFAULT 0;
  DECLARE cur1 CURSOR FOR SELECT TABLE_NAME
                          FROM information_schema.TABLES
                          WHERE
                            TABLE_SCHEMA = (SELECT DATABASE()) AND TABLE_NAME LIKE 'collections%'
                          ORDER BY TABLE_NAME;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  OPEN cur1;
  alter_loop: LOOP FETCH cur1
  INTO tname;
    IF done
    THEN LEAVE alter_loop; END IF;
    SET @SQL := CONCAT("ALTER IGNORE TABLE ", tname,
                       " ADD noise CHAR(32) NOT NULL DEFAULT '' AFTER releaseid COMMENT 'FK to collections table'");
    PREPARE _stmt FROM @SQL;
    EXECUTE _stmt;
    DEALLOCATE PREPARE _stmt;
  END LOOP;
  CLOSE cur1;
END $$

DELIMITER ;

CALL update_collections;
DROP PROCEDURE IF EXISTS update_collections;
