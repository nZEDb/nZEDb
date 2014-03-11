insert into site (setting, value) values ('request_url', 'http://predb_irc.nzedb.com/predb_irc.php?reqid=[REQUEST_ID]&group=[GROUP_NM]');
insert into site (setting, value) values ('lookup_reqids', '1');
ALTER TABLE `releases` ADD COLUMN `reqidstatus` TINYINT(1) NOT NULL DEFAULT '0'  AFTER `relstatus` ;
ALTER TABLE `releases` ADD INDEX `ix_releases_reqidstatus` USING HASH (`reqidstatus` ASC) ;

UPDATE `site` set `value` = '93' where `setting` = 'sqlpatch';
