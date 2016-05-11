# Note this patch does not change user_movies and user_series tables which appear to have
# incorrect column types. They will be handled later.

# Change references of categoryid to categories_id to comply with lithium conventions
ALTER TABLE category_regexes
  CHANGE COLUMN category_id categories_id SMALLINT UNSIGNED NOT NULL DEFAULT '0010'
  COMMENT 'Which category id to put the release in';

ALTER TABLE releases
  CHANGE COLUMN categoryid categories_id INT NOT NULL DEFAULT '0010' COMMENT 'Which category id the release belongs to'
  PARTITION BY RANGE (categories_id) (
  PARTITION misc VALUES LESS THAN (1000),
  PARTITION console VALUES LESS THAN (2000),
  PARTITION movies VALUES LESS THAN (3000),
  PARTITION audio VALUES LESS THAN (4000),
  PARTITION pc VALUES LESS THAN (5000),
  PARTITION tv VALUES LESS THAN (6000),
  PARTITION xxx VALUES LESS THAN (7000),
  PARTITION books VALUES LESS THAN (8000)
  );

ALTER TABLE user_excluded_categories
CHANGE COLUMN categoryid categories_id INT NOT NULL,
COMMENT 'Which category id to exclude';

ALTER TABLE user_movies
CHANGE COLUMN categoryid categories_id INT NOT NULL,
COMMENT 'Which category id to exclude';
