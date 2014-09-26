DROP PROCEDURE IF EXISTS update_collectionsid;
CREATE PROCEDURE update_collectionsid() LANGUAGE SQL DETERMINISTIC CONTAINS SQL READS SQL DATA COMMENT 'changes columns named collectionid to collection_id in binaries_* tables' BEGIN DECLARE done INT DEFAULT FALSE; DECLARE tname VARCHAR(255); DECLARE _stmt VARCHAR(1024); DECLARE cur1 CURSOR FOR  SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = (SELECT DATABASE()) AND TABLE_NAME LIKE 'binaries%' ORDER BY TABLE_NAME; DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE; OPEN cur1; alter_loop: LOOP FETCH cur1 INTO tname; IF done THEN LEAVE alter_loop; END IF; SET @SQL := CONCAT('ALTER IGNORE TABLE ', tname, ' CHANGE COLUMN collectionid collection_id INT(11) UNSIGNED NOT NULL COMMENT \'FK to collections table\''); PREPARE _stmt FROM @SQL; EXECUTE _stmt; DEALLOCATE PREPARE _stmt; END LOOP; CLOSE cur1; END;
CALL update_collectionsid;
DROP PROCEDURE IF EXISTS update_collectionsid;
DROP TRIGGER IF EXISTS delete_collections;
CREATE TRIGGER delete_collections BEFORE DELETE ON collections FOR EACH ROW BEGIN DELETE FROM binaries WHERE collection_id = OLD.id; DELETE FROM parts WHERE collection_id = OLD.id; END;
