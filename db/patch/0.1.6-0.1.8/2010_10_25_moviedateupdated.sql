ALTER TABLE  `movieinfo` ADD  `updateddate` DATETIME NOT NULL;

UPDATE movieinfo SET updateddate = createddate;