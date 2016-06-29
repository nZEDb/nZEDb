# Rename user_movies categories_id column to categories
ALTER TABLE user_movies
CHANGE COLUMN categories_id categories INT NOT NULL,
COMMENT 'Array of categories for user movies';
