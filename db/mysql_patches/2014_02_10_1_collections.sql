DROP INDEX ix_collection_collectionhash ON collections;
ALTER IGNORE TABLE collections ADD UNIQUE INDEX ix_collection_collectionhash(collectionhash);

UPDATE site SET value = '174' WHERE setting = 'sqlpatch';
