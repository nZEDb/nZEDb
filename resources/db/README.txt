When making patches:

1. Do NOT use the back-tick character: `

2. New patches should be created in the appropriate patch directory (.../resources/db/patches/mysql
   for mysql), using the filename format +<number>~<table_name>.sql. Each table should have its on file
   and the number should start at 1 and be incremented for each additional file. (i.e. +1~settings.sql,
   +2~anime_info.sql).

3. Edit the schema files, but do not copy paste your patch files into them,
   if you had an ALTER and an INSERT in your patch for example,
   change the appropriate row(s) in the tsv data file for the INSERT
   and change the CREATE TABLE in the mysql-ddl.sql file, do not add ALTER and INSERT into the mysql-ddl.sql file.

For members of the nZEDb dev team:
  When you are finished, or are merging a PR, running ./commit in the dev (and next-master, master)
    branch will trigger the pre-commit hook which now processes these files. It first updates your database
    to make sure all patches are current, then it will process each new patch file one at a time
    running the SQL against the database. If any line of the patch fails the process is halted so you
    can fix it.
  If all goes well the file is renamed to the appropriate patch level, the sqlpatch setting in the
    database is updated, and it move on to the next file, if any.

----------------------------
Unrelated to making patches:

For MySQL 5.6+ Users that have converted releasesearch to InnoDB, please consider importing the
  schema/innodb_5.6_stopword_tbl.sql file into your main mysql database.  It will vastly improve the
  search results by making it more MyISAM similar in BOOLEAN MODE.  Instructions are in the .sql file.