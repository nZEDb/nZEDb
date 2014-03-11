INSERT INTO tmux (setting, `value`) VALUES ('fix_crap_opt', 'disabled');

UPDATE site SET value = '142' WHERE setting = 'sqlpatch';
