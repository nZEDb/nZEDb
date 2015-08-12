-- This is a fix for an issue where trailer URL's were getting overwritten.
UPDATE movieinfo SET trailer = REPLACE(trailer, 'http://', 'https://') WHERE trailer LIKE '%youtube%';
UPDATE movieinfo SET trailer = REPLACE(trailer, 'watch?v=', 'embed/') WHERE trailer LIKE '%youtube%';