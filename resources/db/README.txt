When making patches:

Do not use `

Update patch in tmux start.php and update sqlpatch # in schema.pgsql and schema.mysql

monitor.php is updated by the repository masters, not via pull requests.

Update both schema.pgsql and schema.mysql

Update sqlpatch in schema.mysql, schema.pgsql, additional patch SQL in sub folders with updated sqlpatch

Do not copy paste patch contents into the schema, add the info where appropriate.

Use correct syntax for postgresql or mysql (some queries in postgresql are not compatible with mysql, vice versa)
