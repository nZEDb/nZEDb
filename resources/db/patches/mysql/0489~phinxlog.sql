# Creates a new table for Phinx to log migration data.
CREATE TABLE phinxlog (
    version BIGINT(20) NOT NULL,
    migration_name VARCHAR(100) NULL,
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    breakpoint TINYINT(1) NOT NULL,
    PRIMARY KEY (version)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
