DROP FUNCTION IF EXISTS `countryCode`;
CREATE FUNCTION `countryCode`(country VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci) RETURNS VARCHAR(5) CHARSET utf8 COLLATE utf8_unicode_ci READS SQL DATA DETERMINISTIC COMMENT 'Convert country name into code, returns NULL if the country does not exist in the table.' BEGIN DECLARE c VARCHAR(5) CHARACTER SET utf8; IF country = '' THEN SET c := NULL; ELSEIF country IS NULL THEN SET c := NULL; ELSE SET country := LOWER(country); SELECT `code` INTO c FROM country WHERE LOWER(`name`) = country; END IF; RETURN c; END;

UPDATE `site` set `value` = '189' where `setting` = 'sqlpatch';
