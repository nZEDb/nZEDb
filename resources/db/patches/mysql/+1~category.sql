#Update category table with new values for Other Misc, hashed, books
UPDATE category SET id = 0000 WHERE id = 7000;
UPDATE category SET id = 0010, parentid= 0000 WHERE id = 7010;
UPDATE category SET id = 0020, parentid= 0000 WHERE id = 7020;
UPDATE category SET id = 7000 WHERE id = 8000;
UPDATE category SET id = 7010, parentid= 7000 WHERE id = 8010;
UPDATE category SET id = 7020, parentid= 7000 WHERE id = 8020;
UPDATE category SET id = 7030, parentid= 7000 WHERE id = 8030;
UPDATE category SET id = 7040, parentid= 7000 WHERE id = 8040;
UPDATE category SET id = 7050, parentid= 7000 WHERE id = 8050;
UPDATE category SET id = 7060, parentid= 7000 WHERE id = 8060;
