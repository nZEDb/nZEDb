INSERT IGNORE INTO `site` (`setting`, `value`) VALUE ('partretentionhours', 72);

UPDATE `site` set `value` = '60' where `setting` = 'sqlpatch';

