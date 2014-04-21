SET @fco := (SELECT
  CASE `value` WHEN ('All') THEN 'All'
  WHEN ('Disabled') THEN 'Disabled'
  ELSE 'Custom'
  END
  FROM tmux WHERE setting = 'fix_crap');

INSERT IGNORE INTO tmux (setting, `value`) VALUES ('fix_crap_opt', @fco);

UPDATE site SET value = '142' WHERE setting = 'sqlpatch';