#Change categoryids of existing releases to new values.
UPDATE releases SET categoryid = 7999 WHERE categoryid = 7050;
UPDATE releases SET categoryid = 7050 WHERE categoryid = 7010;
UPDATE releases SET categoryid = 7010 WHERE categoryid = 7030;
UPDATE releases SET categoryid = 7030 WHERE categoryid = 7020;
UPDATE releases SET categoryid = 7020 WHERE categoryid = 7050;
