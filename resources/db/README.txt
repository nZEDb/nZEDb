When making patches:

1. Do NOT use the tilde character : `

2. Edit the schema files, but do not copy paste your patch files into them,
   if you had an ALTER in your patch for example, change the INSERT in the data file
   and the CREATE in the ddl file (if applicable).

3. Make sure the 'sqlpatch' setting in the site is changed in BOTH data files and you must add an ALTER
   in your patch file to update the 'sqlpatch' number.

4. MySQL and PostgreSQL have different syntax, DO NOT COPY MySQL patches into the PostgreSQL folder,
   look up the syntax first.