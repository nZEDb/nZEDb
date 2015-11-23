#Add releaseid column to dnzb_failures table

ALTER TABLE dnzb_failures ADD releaseid INT(11) UNSIGNED NOT NULL;

#Add index to releaseid

ALTER TABLE dnzb_failures ADD INDEX ix_dnzb_releaseid(releaseid);

#Populate releaseid for existing releases in dnzb_failures table,
#might take a while

UPDATE dnzb_failures df SET releaseid = (SELECT id FROM releases r WHERE df.guid = r.guid);
