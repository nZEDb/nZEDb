UPDATE tmux SET value = 0 WHERE setting = 'releases' AND value = "FALSE";
UPDATE tmux SET value = 1 WHERE setting = 'releases' AND value = "TRUE";

UPDATE site set value = '141' where setting = 'sqlpatch';
