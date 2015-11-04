ALTER IGNORE TABLE countries MODIFY COLUMN country VARCHAR(180)
CHARSET utf8mb4
COLLATE utf8mb4_unicode_ci NOT NULL
COMMENT 'Name of the country.';
