BEGIN
        INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.pictures.erotica.anime', NULL, NULL, 'Anime Manga, Adult');
        EXCEPTION WHEN unique_violation THEN
                -- Ignore duplicate inserts.
END;

UPDATE `site` set `value` = '170' where `setting` = 'sqlpatch';
