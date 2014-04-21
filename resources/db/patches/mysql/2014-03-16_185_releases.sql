update releases set dehashstatus = -7 where dehashstatus < -7;

UPDATE site set value = '185' where setting = 'sqlpatch';
