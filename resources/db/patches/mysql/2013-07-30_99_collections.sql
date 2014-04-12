ALTER TABLE `collections` DROP name;

UPDATE `site` set `value` = '99' where `setting` = 'sqlpatch';
