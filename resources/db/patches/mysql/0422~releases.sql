#Change categoryids of existing releases to new values.
UPDATE releases SET categoryid =
  CASE
    WHEN 7010
      THEN 0010
    WHEN 7020
      THEN 0020
    WHEN 8010
      THEN 7010
    WHEN 8020
      THEN 7020
    WHEN 8030
      THEN 7030
    WHEN 8040
      THEN 7040
    WHEN 8050
      THEN 7050
    WHEN 8060
      THEN 7060
    ELSE categoryid
      END;

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
