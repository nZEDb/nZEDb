ALTER TABLE `releases` CHANGE `relnamestatus` `bitwise` BIGINT NOT NULL DEFAULT 0;
UPDATE releases set bitwise = 0;

UPDATE site SET value = '150' WHERE setting = 'sqlpatch';
