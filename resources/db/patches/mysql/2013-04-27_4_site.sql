INSERT IGNORE INTO `site`
    (`setting`, `value`)
    VALUES
    ('maxpartrepair', 15000);

UPDATE `site` set `value` = '6' where `setting` = 'sqlpatch';
