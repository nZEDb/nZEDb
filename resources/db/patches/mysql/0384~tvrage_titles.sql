# Note: This patch adds the column hascover to tvrage_titles
#	It will be 0 by default.  This is by design.  Once you
#	run /misc/testing/PostProcess/extractTVcovers.php the column
#	will be set to 1 if you have a valid image for that series
#	A follow on patch will be issued to remove imgdata.  Obviously
#	it cannot be done until the user runs the extract script

ALTER TABLE tvrage_titles ADD COLUMN hascover TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Does series have cover art?' AFTER imgdata;
