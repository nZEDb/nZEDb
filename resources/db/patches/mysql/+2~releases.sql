ALTER TABLE releases ADD COLUMN xxxinfo_id INT AFTER imdbid;
CREATE INDEX ix_releases_xxxinfo_id ON releases (xxxinfo_id);
