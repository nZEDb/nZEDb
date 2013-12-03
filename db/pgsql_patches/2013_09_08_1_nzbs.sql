CREATE UNIQUE INDEX "ix_nzbs_message" ON "nzbs" ("message_id");

UPDATE site SET value = '119' WHERE setting = 'sqlpatch';
