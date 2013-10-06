When making patches:

Do not use `

OLD?  --> Update sqlpatch in tmux monitor.php and schema.pgsql and schema.mysql

Update both schema.pgsql and schema.mysql

Update sqlpatch in schema.mysql, schema.pgsql, additional patch SQL in sub folders with updated sqlpatch

Do not copy paste patch contents into the schema, add the info where apropriate.

Use correct syntax for postgresql or mysql (some queries in postgresql are not compatible with mysql, vice versa)
