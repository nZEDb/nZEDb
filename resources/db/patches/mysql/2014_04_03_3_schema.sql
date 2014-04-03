ALTER TABLE countries DROP COLUMN id;
ALTER TABLE countries DROP INDEX ix_countries_name;
ALTER TABLE countries CHANGE COLUMN code code CHAR(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' FIRST;
ALTER TABLE countries ADD PRIMARY KEY (name);

UPDATE site SET value = '196' WHERE setting = 'sqlpatch';
