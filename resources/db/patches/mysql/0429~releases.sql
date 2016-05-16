# Fix category categoryid for 'Other' sub-category to match new table values category.
UPDATE releases SET categoryid = 1999 WHERE categoryid = 1090;
UPDATE releases SET categoryid = 2999 WHERE categoryid = 2020;
UPDATE releases SET categoryid = 3999 WHERE categoryid = 3050;
UPDATE releases SET categoryid = 4999 WHERE categoryid = 4040;
UPDATE releases SET categoryid = 5999 WHERE categoryid = 5050;
UPDATE releases SET categoryid = 6999 WHERE categoryid = 6050;
