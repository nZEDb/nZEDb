INSERT INTO `category` VALUES ('2050', 'HD Other', '2000', '1', null);
INSERT INTO `category` VALUES ('1070', 'XBOX 360 DLC', '1000', '1', null);
UPDATE releases set categoryID = 2050 where name LIKE '%BD%' and name like '%25%';
UPDATE releases set categoryID = 2050 where name LIKE '%BD%' and name like '%50%';
UPDATE releases set categoryID = 1070 where name LIKE '%DLC%' and categoryID = 1050;
DELETE from releases where name like '%abgx%' and regexid = 202; 
UPDATE binaries set procstat = 0 where regexID = 202 and name like '%abgx%';