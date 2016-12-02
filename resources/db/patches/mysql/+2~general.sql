DROP PROCEDURE IF EXISTS loop_cbpm;

DELIMITER $$

CREATE PROCEDURE loop_cbpm(IN method CHAR(10))
  COMMENT 'Performs tasks on All CBPM tables one by one -- REPAIR/ANALYZE/OPTIMIZE or DROP/TRUNCATE'

  main:BEGIN
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
      THEN LEAVE alter_loop; END IF;
      SET @SQL := CONCAT(method, " TABLE ", tname);
      PREPARE _stmt FROM @SQL;
      EXECUTE _stmt;
      DEALLOCATE PREPARE _stmt;
    END LOOP;
    CLOSE cur1;
  END; $$

DELIMITER ;

DROP PROCEDURE IF EXISTS delete_release;

DELIMITER $$

CREATE PROCEDURE delete_release(IN is_numeric BOOLEAN, IN identifier VARCHAR(40))
  COMMENT 'Cascade deletes release from child tables when parent row is deleted'
  COMMENT 'If is_numeric is true, identifier should be the releases_id, if false the guid'

  main:BEGIN

    DECLARE where_constr VARCHAR(255) DEFAULT '';

    IF is_numeric IS TRUE
    THEN SET where_constr = CONCAT('r.id = ', identifier);

    ELSEIF is_numeric IS FALSE
    THEN SET where_constr = CONCAT("r.guid = '", identifier, "'");

    ELSE LEAVE main;
    END IF;

    DELETE r, rn, rc, uc, rf, ra, rs, rv, re, df, rg
    FROM releases r
      LEFT OUTER JOIN release_nfos rn ON rn.releases_id = r.id
      LEFT OUTER JOIN release_comments rc ON rc.releases_id = r.id
      LEFT OUTER JOIN users_releases uc ON uc.releases_id = r.id
      LEFT OUTER JOIN release_files rf ON rf.releases_id = r.id
      LEFT OUTER JOIN audio_data ra ON ra.releases_id = r.id
      LEFT OUTER JOIN release_subtitles rs ON rs.releases_id = r.id
      LEFT OUTER JOIN video_data rv ON rv.releases_id = r.id
      LEFT OUTER JOIN releaseextrafull re ON re.releases_id = r.id
      LEFT OUTER JOIN dnzb_failures df ON df.release_id = r.id
      LEFT OUTER JOIN releases_groups rg ON rg.releases_id = r.id
    WHERE where_constr;

  END; $$

DELIMITER ;