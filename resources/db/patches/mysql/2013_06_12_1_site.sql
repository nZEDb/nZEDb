INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('miscotherretentionhours', '0');

UPDATE `site` set `value` = '75' where `setting` = 'sqlpatch';
