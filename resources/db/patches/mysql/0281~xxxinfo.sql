ALTER TABLE xxxinfo DROP INDEX ix_xxxinfo_title;

ALTER IGNORE TABLE xxxinfo ADD UNIQUE INDEX ix_xxxinfo_title (title);

UPDATE releases SET xxxinfo_id = 0 WHERE categoryid BETWEEN 6000 AND 6999 AND xxxinfo_id > 0 AND xxxinfo_id NOT IN (SELECT id FROM xxxinfo);