# Fix categories so all 'others' are at x999 for the category.
UPDATE category SET id = 1999 WHERE id = 1090;
UPDATE category SET id = 2999 WHERE id = 2020;
UPDATE category SET id = 3999 WHERE id = 3050;
UPDATE category SET id = 4999 WHERE id = 4040;
UPDATE category SET id = 5999 WHERE id = 5050;
UPDATE category SET id = 6999 WHERE id = 6050;
