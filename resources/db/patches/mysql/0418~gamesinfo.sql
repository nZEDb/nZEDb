# Fix genreid to genre_id that was changed in schema at time of SQL patch 278, but not patched at the time.
ALTER IGNORE TABLE gamesinfo CHANGE COLUMN genreid genre_id INT(10) NULL DEFAULT NULL COMMENT 'FK to genres.id';
