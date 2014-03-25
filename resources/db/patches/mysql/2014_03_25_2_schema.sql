ALTER TABLE releasesearch ADD INDEX ix_releasesearch_releaseid (releaseid);

UPDATE site set value = '188' where setting = 'sqlpatch';
