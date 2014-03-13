INSERT IGNORE INTO `tmux`
    (`setting`, `value`)
    VALUES
    ('CRAP_TIMER','30'), ('FIX_CRAP','FALSE'), ('TV_TIMER','43200'), ('UPDATE_TV','FALSE'), ('HTOP','FALSE'), ('NMON','FALSE'), ('BWMNG','FALSE'), ('MYTOP','FALSE'), ('CONSOLE','FALSE'), ('VNSTAT','FALSE'), ('VNSTAT_ARGS',NULL), ('TCPTRACK','FALSE'), ('TCPTRACK_ARGS','-i eth0 port 443');


UPDATE `site` set `value` = '20' where `setting` = 'sqlpatch';
