#Change categoryids of existing releases to new values.
UPDATE releases SET categoryid = 0010 WHERE categoryid = 7010;
UPDATE releases SET categoryid = 0020 WHERE categoryid = 7020;
UPDATE releases SET categoryid = 7010 WHERE categoryid = 8010;
UPDATE releases SET categoryid = 7020 WHERE categoryid = 8020;
UPDATE releases SET categoryid = 7030 WHERE categoryid = 8030;
UPDATE releases SET categoryid = 7040 WHERE categoryid = 8040;
UPDATE releases SET categoryid = 7050 WHERE categoryid = 8050;
UPDATE releases SET categoryid = 7060 WHERE categoryid = 8060;
