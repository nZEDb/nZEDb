/* Force users' saburl entry to have a trailing slash */
UPDATE users
SET saburl = CONCAT(saburl, '/')
WHERE RIGHT(saburl, 1) != '/';
