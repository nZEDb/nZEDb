UPDATE tmux SET value = 0 WHERE setting = 'BINARIES' AND value = "FALSE";
UPDATE tmux SET value = 1 WHERE setting = 'BINARIES' AND value = "TRUE";;

UPDATE site SET value = 122 WHERE setting = 'sqlpatch';
