SELECT CONCAT('rename table ', column_name, ' TO ' , lower(column_name) , ';') FROM information_schema.columns WHERE table_schema = 'nzedb';

UPDATE site SET value = '163' WHERE setting = 'sqlpatch'; 
