# This patch will remove column and index added by patch 383

#Drop the text_hash column
ALTER TABLE release_comments DROP text_hash;

#Drop the trigger
DROP TRIGGER IF EXISTS insert_MD5;

#Drop the index
ALTER TABLE release_comments DROP INDEX ix_release_comments_hash_releaseid;
