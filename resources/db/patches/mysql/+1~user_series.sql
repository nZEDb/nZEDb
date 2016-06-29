# Rename user_series categoryid column to categories
ALTER TABLE user_series
CHANGE COLUMN categoryid categories INT NOT NULL,
COMMENT 'Array of categories for user tv shows';
