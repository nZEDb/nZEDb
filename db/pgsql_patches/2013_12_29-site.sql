SELECT 'ALTER TABLE '||quote_ident(t.relname)||' RENAME TO '||t.relname||';' FROM pg_class t, pg_namespace s WHERE s.oid = t.relnamespace AND s.nspname = 'public' AND t.relkind='r' AND t.relname != lower(t.relname) ORDER BY 1;
SELECT 'ALTER TABLE '||quote_ident(t.relname)|| ' RENAME COLUMN '||quote_ident(a.attname)|| ' TO '||a.attname||';' FROM pg_class t, pg_namespace s, pg_attribute a WHERE s.oid = t.relnamespace AND s.nspname = 'public' AND t.relkind='r' AND t.relname != lower(t.relname) AND a.attrelid = t.oid AND NOT a.attisdropped AND a.attnum > 0 AND a.attname != lower(a.attname) ORDER BY 1;

UPDATE site SET value = '163' WHERE setting = 'sqlpatch'; 
