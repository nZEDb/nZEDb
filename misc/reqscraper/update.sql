ALTER TABLE  `feed` ADD  `updatemins` TINYINT( 3 ) NOT NULL DEFAULT  '55' AFTER  `titleregex`;

ALTER TABLE  `item` ADD UNIQUE  `ix_reqid_title` (  `reqid` ,  `title` );

ALTER TABLE  `item` DROP INDEX  `ix_item_guid`;