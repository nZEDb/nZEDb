ALTER TABLE `releases` RENAME COLUMN `relnamestatus` TO `bitwise`;
ALTER TABLE `releases` ALTER COLUMN `bitwise` TYPE bigint;
UPDATE releases set bitwise = 0;

UPDATE site SET value = '150' WHERE setting = 'sqlpatch';
