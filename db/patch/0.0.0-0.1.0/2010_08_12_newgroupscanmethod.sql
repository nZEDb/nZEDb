ALTER TABLE `site`  ADD `newgroupscanmethod` INT NOT NULL AFTER `compressedheaders`,  ADD `newgroupdaystoscan` INT NOT NULL AFTER `newgroupscanmethod`,  ADD `newgroupmsgstoscan` INT NOT NULL AFTER `newgroupdaystoscan`;

ALTER TABLE `site`  ADD `maxmssgs` INT NOT NULL AFTER `compressedheaders`;

UPDATE `site` SET `maxmssgs` = '20000', `newgroupscanmethod` = '0', `newgroupdaystoscan` = '3', `newgroupmsgstoscan` = '50000' WHERE `id` = 1;