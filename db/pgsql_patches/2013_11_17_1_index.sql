DROP INDEX IF EXISTS "partrepair_groupid_attempts" CASCADE;
CREATE INDEX "partrepair_groupid_attempts" ON "partrepair" ("groupid", "attempts");
DROP INDEX IF EXISTS "partrepair_numberid_groupid_attempts" CASCADE;
CREATE INDEX "partrepair_numberid_groupid_attempts" ON "partrepair" ("numberid", "groupid", "attempts");

UPDATE site SET value = '148' WHERE setting = 'sqlpatch';
