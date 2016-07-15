# Rename user_series categoryid column to categories
ALTER TABLE user_series
CHANGE COLUMN categoryid categories VARCHAR(64) NULL DEFAULT NULL
COMMENT 'List of categories for user tv shows';
