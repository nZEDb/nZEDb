DROP PROCEDURE IF EXISTS alter_tables;
/* Create the stored procedure to alter tables -- change to myisam if version < 5.6 */
CREATE PROCEDURE alter_tables() BEGIN SELECT version() regexp '^[0-5]\.[0-5]' into @version; IF @version = 1 THEN ALTER TABLE bookinfo engine=myisam; ALTER TABLE consoleinfo engine=myisam; ALTER TABLE musicinfo engine=myisam; END IF; END;
/* Execute the stored procedures */
CALL alter_tables();
/* Don't forget to drop the stored procedure when you're done! */
DROP PROCEDURE IF EXISTS alter_tables;

ALTER TABLE musicinfo ADD FULLTEXT INDEX ix_musicinfo_artist_title_ft (artist, title);
ALTER TABLE bookinfo ADD FULLTEXT INDEX ix_bookinfo_author_title_ft (author, title);
ALTER TABLE consoleinfo ADD FULLTEXT INDEX ix_consoleinfo_title_platform_ft (title, platform);
