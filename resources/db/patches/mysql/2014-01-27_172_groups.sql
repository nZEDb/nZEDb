INSERT IGNORE INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.pictures.erotica.anime', NULL, NULL, 'Anime Manga, Adult');
UPDATE predb SET nfo = REPLACE(nfo, 'nzb.isasecret.com', 'www.newshost.co.za');

UPDATE `site` SET `value` = '172' WHERE `setting` = 'sqlpatch';
