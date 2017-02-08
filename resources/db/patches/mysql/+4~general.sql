#
# Update loop_cbpm procedure to include multigroup tables
#

DROP PROCEDURE IF EXISTS loop_cbpm;

DELIMITER $$

CREATE PROCEDURE loop_cbpm(IN method CHAR(10))
  COMMENT 'Performs tasks on All CBPM tables one by one -- REPAIR/ANALYZE/OPTIMIZE or DROP/TRUNCATE'

    main: BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE tname VARCHAR(255) DEFAULT '';
    DECLARE regstr VARCHAR(255) CHARSET utf8 COLLATE utf8_general_ci DEFAULT '';

    DECLARE cur1 CURSOR FOR
      SELECT TABLE_NAME
      FROM information_schema.TABLES
      WHERE
        TABLE_SCHEMA = (SELECT DATABASE())
        AND TABLE_NAME REGEXP regstr
      ORDER BY TABLE_NAME ASC;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    IF method NOT IN ('repair', 'analyze', 'optimize', 'drop', 'truncate')
    THEN LEAVE main; END IF;

    IF method = 'drop' THEN SET regstr = '^(collections|binaries|parts|missed_parts)_[0-9]+$';
    ELSE SET regstr = '^(multigroup_)?(collections|binaries|parts|missed_parts)(_[0-9]+)?$';
    END IF;

    OPEN cur1;
    cbpm_loop: LOOP FETCH cur1
    INTO tname;
      IF done
      THEN LEAVE cbpm_loop; END IF;
      SET @SQL := CONCAT(method, ' TABLE ', tname);
      PREPARE _stmt FROM @SQL;
      EXECUTE _stmt;
      DEALLOCATE PREPARE _stmt;
    END LOOP;
    CLOSE cur1;
  END;
$$

