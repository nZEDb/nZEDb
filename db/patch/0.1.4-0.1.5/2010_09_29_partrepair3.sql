ALTER TABLE `partrepair` DROP INDEX  `ix_numberID_groupID` ,
ADD UNIQUE  `ix_partrepair_numberID_groupID` (  `numberID` ,  `groupID` ) ;