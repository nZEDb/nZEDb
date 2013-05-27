INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('partretentionhours', 72);

UPDATE `site` set `value` = '57' where `setting` = 'sqlpatch';

