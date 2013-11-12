UPDATE tmux SET value = 0 WHERE setting = 'post_non' AND value = "FALSE";
UPDATE tmux SET value = 1 WHERE setting = 'post_non' AND value = "TRUE";

UPDATE site SET value = '144' WHERE setting = 'sqlpatch';
