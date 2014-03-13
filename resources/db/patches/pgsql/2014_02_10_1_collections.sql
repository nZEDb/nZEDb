DROP INDEX ix_collection_collectionhash ON collections;
CREATE UNIQUE INDEX ix_collection_collectionhash ON collections(collectionhash);

UPDATE site SET value = '174' WHERE setting = 'sqlpatch';
