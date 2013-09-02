ALTER TABLE nzbs change `article-number` articlenumber varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0';

UPDATE site SET value = '113' WHERE setting = 'sqlpatch';
