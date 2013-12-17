DELETE FROM `site` WHERE `setting` = 'siteseed';

UPDATE `site` SET value = '158' WHERE setting = 'sqlpatch';
