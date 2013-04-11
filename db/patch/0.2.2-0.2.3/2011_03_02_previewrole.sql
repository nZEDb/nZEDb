ALTER TABLE userroles ADD canpreview INT NOT NULL DEFAULT 0;
update userroles  set canpreview = 1 where id in (2,4);