ALTER TABLE  `binaries` DROP INDEX  `ix_binary_name`;
ALTER TABLE  `binaries` DROP INDEX  `ix_name_fromname_groupID`;

ALTER TABLE  `binaries` ADD  `binaryhash` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER  `reltotalpart`;

UPDATE binaries SET binaryhash = MD5( CONCAT( name, fromname, groupID ) );

ALTER TABLE  `binaries` ADD INDEX  `ix_binary_binaryhash` (  `binaryhash` );