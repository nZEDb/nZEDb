DELETE FROM menu WHERE href = 'console';
INSERT INTO menu
    (`href`,
     `title`,
     `tooltip`,
     `role`,
     `ordinal`,
     `menueval`)
VALUES (
        'console',
        'Console',
        'Browse for Games',
        '1',
        '48',
        '');
        
alter table users add consoleview int not null default 1;