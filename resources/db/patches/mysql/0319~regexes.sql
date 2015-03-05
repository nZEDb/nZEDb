ALTER TABLE release_naming_regexes MODIFY COLUMN group_regex VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'This is a regex to match against usenet groups';
ALTER TABLE release_naming_regexes MODIFY COLUMN regex VARCHAR(5000) NOT NULL DEFAULT '' COMMENT 'Regex used for extracting name from subject';
ALTER TABLE release_naming_regexes MODIFY COLUMN description VARCHAR(1000) NOT NULL DEFAULT '' COMMENT 'Optional extra details on this regex';
ALTER TABLE collection_regexes MODIFY COLUMN group_regex VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'This is a regex to match against usenet groups';
ALTER TABLE collection_regexes MODIFY COLUMN regex VARCHAR(5000) NOT NULL DEFAULT '' COMMENT 'Regex used for collection grouping';
ALTER TABLE collection_regexes MODIFY COLUMN description VARCHAR(1000) NOT NULL DEFAULT '' COMMENT 'Optional extra details on this regex';
DROP TABLE IF EXISTS category_regexes;
CREATE TABLE category_regexes (
  id          INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  group_regex VARCHAR(255)        NOT NULL DEFAULT ''     COMMENT 'This is a regex to match against usenet groups',
  regex       VARCHAR(5000)       NOT NULL DEFAULT ''     COMMENT 'Regex used to match a release name to categorize it',
  status      TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'    COMMENT '1=ON 0=OFF',
  description VARCHAR(1000)       NOT NULL DEFAULT ''     COMMENT 'Optional extra details on this regex',
  ordinal     INT SIGNED          NOT NULL DEFAULT '0'    COMMENT 'Order to run the regex in',
  category_id SMALLINT UNSIGNED   NOT NULL DEFAULT '7010' COMMENT 'Which category id to put the release in',
  PRIMARY KEY (id),
  INDEX ix_category_regexes_group_regex (group_regex),
  INDEX ix_category_regexes_status      (status),
  INDEX ix_category_regexes_ordinal     (ordinal),
  INDEX ix_category_regexes_category_id (category_id)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 100000;
INSERT INTO category_regexes (id, group_regex, regex, status, description, ordinal, category_id)
  VALUES (
    1,
    'alt\\.binaries\\.sony\\.psvita',
    '/.*/',
    1,
    '',
    50,
    1120
);
