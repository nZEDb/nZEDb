# This patch will create a temp table like release_comments
# Then it will alter the nzb_guid column to be BINARY(16)
# It will insert all data from release_comments into the temp table while unhexing nzb_guid
# Drop old release_comments table
# Rename new release_comments temp table to release_comments

CREATE TABLE release_comments_tmp LIKE release_comments;
ALTER TABLE release_comments_tmp MODIFY nzb_guid BINARY(16) NOT NULL DEFAULT '0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0';
INSERT INTO release_comments_tmp (id, releaseid, text, username, user_id, createddate, host, shared, shareid, siteid, nzb_guid)
SELECT id, releaseid, text, username, user_id, createddate, host, shared, shareid, siteid, UNHEX(nzb_guid) FROM release_comments;

DROP TABLE release_comments;
ALTER TABLE release_comments_tmp RENAME release_comments;