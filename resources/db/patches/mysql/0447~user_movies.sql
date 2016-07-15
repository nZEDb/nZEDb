# Rename user_movies categories_id column to categories
ALTER TABLE user_movies
CHANGE COLUMN categories_id categories VARCHAR(64) NULL DEFAULT NULL
COMMENT 'List of categories for user movies';
