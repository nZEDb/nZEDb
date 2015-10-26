# Alter videos table to add anidb id column

ALTER IGNORE TABLE videos ADD COLUMN anidb MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for anidb site' AFTER started;
