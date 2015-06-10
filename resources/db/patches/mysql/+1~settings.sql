INSERT INTO settings (section, subsection, name, value, hint, setting)
  VALUES (
    'tmux',
    'running',
    'exit',
    0,
    'Determines if the running tmux monitor script should exit. If 0 nothing changes; if positive the script should exit gracefully (allowing all panes to finish); if negative the script should die as soon as possible.',
    'tmux.running.exit'
  );
