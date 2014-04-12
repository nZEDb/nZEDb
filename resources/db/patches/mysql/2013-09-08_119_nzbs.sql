ALTER IGNORE TABLE nzbs ADD UNIQUE INDEX ix_nzbs_message (message_id);

UPDATE site SET value = '119' WHERE setting = 'sqlpatch';
