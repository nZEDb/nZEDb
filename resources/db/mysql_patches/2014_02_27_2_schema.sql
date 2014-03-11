ALTER TABLE logging ADD id INT PRIMARY KEY AUTO_INCREMENT;

UPDATE `site` set `value` = '181' where `setting` = 'sqlpatch';
