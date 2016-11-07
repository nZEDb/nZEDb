# Change reqid server used

UPDATE settings SET value = 'http://reqid.newznab-tmux.pw/index.php' WHERE section = '' AND subsection = '' AND name = 'request_url';
