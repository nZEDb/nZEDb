#Change value for postprocessing non-amazon for those who had it set to properly renamed releases
UPDATE tmux SET value = '1' where setting = 'post_non' AND value = '2';
