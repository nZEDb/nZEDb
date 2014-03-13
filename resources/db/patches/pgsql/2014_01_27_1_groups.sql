BEGIN
        INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.pictures.erotica.anime', NULL, NULL, 'Anime Manga, Adult');
        EXCEPTION WHEN unique_violation THEN
                -- Ignore duplicate inserts.
END;
UPDATE predb SET nfo = REPLACE(nfo, 'nzb.isasecret.com', 'www.newshost.co.za');

UPDATE `site` SET `value` = '172' WHERE `setting` = 'sqlpatch';
