ALTER TABLE consoleinfo CHANGE COLUMN genreid genre_id INT(11) UNSIGNED NOT NULL COMMENT 'FK to genres';
ALTER TABLE gamesinfo CHANGE COLUMN genreid genre_id INT(11) UNSIGNED NOT NULL COMMENT 'FK to genres';
ALTER TABLE musicinfo CHANGE COLUMN genreid genre_id INT(11) UNSIGNED NOT NULL COMMENT 'FK to genres';
