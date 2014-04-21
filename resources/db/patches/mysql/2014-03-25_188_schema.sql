ALTER TABLE releasesearch ADD INDEX ix_releasesearch_releaseid (releaseid);

UPDATE site SET value = '188' WHERE setting = 'sqlpatch';
