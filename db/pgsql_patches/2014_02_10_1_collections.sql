DROP INDEX collections_collectionhash;
CREATE UNIQUE INDEX ix_collections_collectionhash ON collections(collectionhash);

UPDATE site SET value = '174' WHERE setting = 'sqlpatch';
