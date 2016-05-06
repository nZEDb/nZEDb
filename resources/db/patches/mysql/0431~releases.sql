#Replacing triggers on release table
DELIMITER $$

DROP TRIGGER IF EXISTS check_insert $$
CREATE TRIGGER check_insert BEFORE INSERT ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1;ELSEIF NEW.name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\s?\\[' THEN SET NEW.isrequestid = 1; END IF; END;$$

DROP TRIGGER IF EXISTS check_update $$
CREATE TRIGGER check_update BEFORE UPDATE ON releases FOR EACH ROW BEGIN IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}' THEN SET NEW.ishashed = 1;ELSEIF NEW.name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\s?\\[' THEN SET NEW.isrequestid = 1;END IF;END;$$

DELIMITER ;

#fixing isrequestid - this will take a while
UPDATE releases set isrequestid = 1 where isrequestid = 0 and name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\s?\\[';