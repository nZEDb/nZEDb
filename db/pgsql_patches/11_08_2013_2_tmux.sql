DECLARE v_FCO TEXT;
BEGIN
  v_FCO := (SELECT value
  CASE WHEN ('All') THEN 'All'
  WHEN ('Disabled') THEN 'Disabled'
  ELSE 'Custom'
  END
  FROM tmux WHERE setting = 'fix_crap');

INSERT INTO tmux (setting, value) VALUES ('fix_crap_opt', v_FCO);

UPDATE site SET value = '142' WHERE setting = 'sqlpatch';