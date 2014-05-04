When making patches:

1. Do NOT use the back-tick character : `

2. Edit the schema files, but do not copy paste your patch files into them,
   if you had an ALTER in your patch for example, change the appropriate row(s) in the data file
   and the CREATE in the ddl file (if applicable).

3. Make sure the 'sqlpatch' setting in the settings is changed in BOTH data files. ALTER in the patch file is no longer needed.

4. MySQL and PostgreSQL have different syntax, DO NOT COPY MySQL patches into the PostgreSQL folder,
   look up the syntax first.

5. For MySQL 5.6+ Users that have converted releasesearch to InnoDB, please consider importing the
   new innodb_5.6_stopword_tbl.sql file into your main mysql database.  It will vastly improve the
   search results by making it more MyISAM similar in BOOLEAN MODE.  Instructions are in the .sql file.
