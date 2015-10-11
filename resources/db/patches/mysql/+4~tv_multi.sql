# This patch migrates the valid TvRage data to the new videos/tv_info/episodes table

# First we will populate videos table with existing ragedata
INSERT INTO videos (type, title, countries_id, started, tvrage, source)
	(SELECT 0, tvrt.releasetitle, tvrt.country, DATE(MIN(tvre.airdate)), tvrt.rageid, 3 FROM tvrage_titles tvrt INNER JOIN tvrage_episodes tvre USING (rageid) WHERE tvrt.rageid > 0);

# Next we will populate the tv_info table with scraped series data from TvRage
INSERT INTO tv_info (videos_id, summary, publisher, image)
	(SELECT v.id, tvrt.description, '', tvrt.hascover FROM videos v INNER JOIN tvrage_titles tvrt USING (rageid));

# Lastly we will populate the tv_episodes table with scraped episode data from TvRage
INSERT INTO tv_episodes (videos_id, series, episode, se_complete, title, firstaired, summary)
	(SELECT v.id, SUBSTRING_INDEX(tvre.fullep, 'x', 1), SUBSTRING_INDEX(tvre.fullep, 'x', -1), tvre.fullep, tvre.eptitle, DATE(tvre.airdate), '' FROM videos v INNER JOIN tvrage_episodes tvre USING (rageid));
