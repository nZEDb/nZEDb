ALTER TABLE releases ADD `rarinnerfilecount` INT NOT NULL DEFAULT 0;

UPDATE releases 
INNER JOIN 
(SELECT releaseID, COUNT(*) AS num FROM releasefiles GROUP BY releaseID) b ON b.releaseID = releases.ID
SET rarinnerfilecount = b.num;