# Rename user_series categoryid column to categories_id
ALTER TABLE user_series
CHANGE COLUMN categoryid categories_id INT NOT NULL,
COMMENT 'FK to categories.id';
