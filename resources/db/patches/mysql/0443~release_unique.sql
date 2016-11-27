  DROP TABLE IF EXISTS release_unique;
  CREATE TABLE release_unique (
  releases_id   INT(11) UNSIGNED  NOT NULL COMMENT 'FK to releases.id.',
  uniqueid      BINARY(16)        NOT NULL DEFAULT '0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0' COMMENT 'Unique_ID from mediainfo.',
  PRIMARY KEY (releases_id, uniqueid)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

  DROP PROCEDURE IF EXISTS populate_uniqueids;

  DELIMITER $$
  CREATE PROCEDURE populate_uniqueids()
  LANGUAGE SQL DETERMINISTIC CONTAINS SQL READS SQL DATA COMMENT 'Populates release_unique IDs table'
    BEGIN
      DROP TABLE IF EXISTS temp_uniqueids;
      CREATE TEMPORARY TABLE temp_uniqueids (
        releases_id INT(11) UNSIGNED NOT NULL,
        uniqueid VARCHAR(255) CHARSET utf8 COLLATE utf8_unicode_ci,
        PRIMARY KEY (releases_id, uniqueid)
      );

      INSERT IGNORE INTO temp_uniqueids (releases_id, uniqueid)
        SELECT
          releases_id,
          REPLACE(SUBSTRING_INDEX(ExtractValue(mediainfo, '//Unique_ID'), 'x', -1), ')', '')
        FROM releaseextrafull
        WHERE mediainfo IS NOT NULL
        AND LENGTH(mediainfo) > 0;

      DELETE FROM temp_uniqueids WHERE uniqueid NOT REGEXP '^.{32}$';

      INSERT INTO release_unique (releases_id, uniqueid)
        SELECT releases_id, UNHEX(uniqueid) FROM temp_uniqueids;

      DROP TABLE temp_uniqueids;

    END $$
  DELIMITER ;

  CALL populate_uniqueids();
  DROP PROCEDURE IF EXISTS populate_uniqueids;
