#Change categoryids of existing releases to new values.
UPDATE releases SET categoryid = 0010 WHERE categoryid = 7010;
UPDATE releases SET categoryid = 0020 WHERE categoryid = 7020;
UPDATE releases SET categoryid = 7010 WHERE categoryid = 8010;
UPDATE releases SET categoryid = 7020 WHERE categoryid = 8020;
UPDATE releases SET categoryid = 7030 WHERE categoryid = 8030;
UPDATE releases SET categoryid = 7040 WHERE categoryid = 8040;
UPDATE releases SET categoryid = 7050 WHERE categoryid = 8050;
UPDATE releases SET categoryid = 7060 WHERE categoryid = 8060;


#Partition the releases table with new values.
ALTER TABLE releases PARTITION BY RANGE (categoryid) (
PARTITION misc VALUES LESS THAN (1000),
PARTITION console VALUES LESS THAN (2000),
PARTITION movies VALUES LESS THAN (3000),
PARTITION audio VALUES LESS THAN (4000),
PARTITION pc VALUES LESS THAN (5000),
PARTITION tv VALUES LESS THAN (6000),
PARTITION xxx VALUES LESS THAN (7000),
PARTITION books VALUES LESS THAN (8000));
