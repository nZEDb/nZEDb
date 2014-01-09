<?php

require_once dirname(__FILE__) . '/../../../www/config.php';
//require_once nZEDb_LIB . 'framework/db.php';

$db = new DB();
if ($db->dbSystem() == "pgsql")
	exit("Currently this only works for mysql.\nMaybe someone can make it work with postgresql?\n");

function runQuery($db, $sql, $runQueries)
{
	if ($runQueries)
	{
		$db->queryDirect($sql);
		return true;
	}
	else
	{
		echo $sql.";\n";
		return true;
	}
}

function runQueryupdate($db, $sql, $runQueries)
{
	if ($runQueries)
	{
		$db->queryExec($sql);
		return true;
	}
	else
	{
		echo $sql.";\n";
		return true;
	}
}

function convertTable($db, $nZEDbschema, $tableName, $sql, $runQueries)
{
	if ($runQueries)
		echo "Converting: ".$tableName." ";

	$renameSQL = "RENAME TABLE ".$nZEDbschema.".".$tableName." TO ".$nZEDbschema.".original_".$tableName;
	$createSQL = "CREATE TABLE ".$nZEDbschema.".".$tableName." LIKE ".$nZEDbschema.".original_".$tableName;

	if (!runQuery($db, $renameSQL, $runQueries))
		exit("Unable to rename table Running the following SQL: ".$renameSQL."\nConversion Stopped....\n");

	if (!runQuery($db, $createSQL, $runQueries))
		exit("Unable to create table Running the following SQL: ".$createSQL."\nConversion Stopped....\n");

	if (!runQuery($db, $sql, $runQueries))
		die("Unable to move the data... Running: ".$sql."\nConversion Stopped....\n");

	if ($runQueries)
		echo "Done.\n";
}

function truncateTable($db, $tableName, $runQueries)
{
	if (!runQuery($db, "TRUNCATE TABLE ".$tableName, $runQueries))
		exit("Unable to truncate: ".$tableName."\nConversion Stopped....\n");
}

	// Check argument count.
	if (!isset($argv[1]) || !isset($argv[2]) || !isset($argv[3]))
	{
		passthru("clear");
		echo "Usage: newznab_schema nZEDB_schema true/false\n"
		."example: php convert_from_newznab.php newznab nzedb true\n\n"
		."newznab_schema: Schema where your newznab install is located. The database name. The current user in your config.php file must have access to this schema\n"
		."nZEDB_schema: Schema where you want the newznab data converted too. The database name. The schema must be populated and will be wiped clean except the sites and categories tables\n"
		."true/false: false = Show the queries but do not run.  true means you understand the risks and want to convert the data (Your old data will not be touched\n\n"
		."NOTE: This is experimental and there is a possibility that this will not work correctly.  Please let us know if it doesn't work correctly, but we are not responsible for any lost data.\n"
		."      You will have to start any backfilling and processing over again since we use a different mechanism for processing releases\n\n";
		exit(1);
	}

	$nn_schema = $argv[1];
	$nZEDB_schema = $argv[2];
	$runQueries = $argv[3] == "true";

	// This converts from the schema newznab to the schema nZEDb.

	echo "Resetting Collections/Binaries/Parts/PartRepair\n";
	truncateTable($db, $nZEDB_schema.".collections", $runQueries);
	truncateTable($db, $nZEDB_schema.".binaries", $runQueries);
	truncateTable($db, $nZEDB_schema.".parts", $runQueries);
	truncateTable($db, $nZEDB_schema.".partrepair", $runQueries);

	echo "Converting from newznab to nZEDb.... This will take a while....\n\n";

	convertTable($db, $nZEDB_schema, "anidb", "INSERT INTO ".$nZEDB_schema.".anidb (airdates, anidbid, categories, characters, creators, description, enddate, episodetitles, epnos, id, picture, rating, related, startdate, title, type, unixtime) ".
		"SELECT airdates, anidbID, categories, characters, creators, description, enddate, episodetitles, epnos, ID, picture, rating, related, startdate, title, type, UNIX_TIMESTAMP(createddate) FROM ".$nn_schema.".anidb", $runQueries);

	convertTable($db, $nZEDB_schema, "animetitles", "INSERT INTO ".$nZEDB_schema.".animetitles (anidbid, title, unixtime) ".
		"SELECT anidbID, title, UNIX_TIMESTAMP(createddate) FROM ".$nn_schema.".animetitles", $runQueries);

	echo "Skipping binaries table: Different in nZEDb\n";

	convertTable($db, $nZEDB_schema, "binaryblacklist", "INSERT INTO ".$nZEDB_schema.".binaryblacklist (id, groupname, regex, msgcol, optype, status, description) ".
		"SELECT ID, groupname, regex, msgcol, optype, status, description FROM ".$nn_schema.".binaryblacklist", $runQueries);

	// You lose genreID and dewey.
	convertTable($db, $nZEDB_schema, "bookinfo", "INSERT INTO ".$nZEDB_schema.".bookinfo (id, title , author, asin, isbn, ean, url, publisher, publishdate, pages, overview, cover, createddate, updateddate) ".
		"SELECT ID, title , author, asin, isbn, ean, url, publisher, publishdate, pages, review, cover, createddate, updateddate FROM ".$nn_schema.".bookinfo", $runQueries);

	echo "Skipping category table: using nZEDb's version\n";

	convertTable($db, $nZEDB_schema, "consoleinfo", "INSERT INTO ".$nZEDB_schema.".consoleinfo (asin, cover, createddate, esrb, genreid, id, platform, publisher, releasedate, review, salesrank, title, updateddate, url) ".
		"SELECT asin, cover, createddate, esrb, genreID, ID, platform, publisher, releasedate, review, salesrank, title, updateddate, url FROM ".$nn_schema.".consoleinfo", $runQueries);

	convertTable($db, $nZEDB_schema, "content", "INSERT INTO ".$nZEDB_schema.".content (id, title, url, body, metadescription, metakeywords, contenttype, showinmenu, status, ordinal, role) ".
		"SELECT id, title, url, body, metadescription, metakeywords, contenttype, showinmenu, status, ordinal, role FROM ".$nn_schema.".content", $runQueries);

	echo "episodeinfo = tvrageepisodes in nZEDb\n";
	// Convert episodeinfo to tvrageepisodes - You loose (tvdbID, imdbID, director, gueststars, overview, rating, writer, epabsolute)
	convertTable($db, $nZEDB_schema, "tvrageepisodes", "INSERT INTO ".$nZEDB_schema.".tvrageepisodes (id, rageid, showtitle, airdate, link, fullep, eptitle) ".
		"SELECT MIN(ID), rageID, showtitle, MIN(airdate), link, fullep, eptitle FROM ".$nn_schema.".episodeinfo where rageID <> 0 GROUP BY rageID, fullep", $runQueries);

	convertTable($db, $nZEDB_schema, "forumpost", "INSERT INTO ".$nZEDB_schema.".forumpost (forumid, parentid, userid, subject, message, locked, sticky, replies, createddate, updateddate) ".
		"SELECT forumID, parentID, userID, subject, message, locked, sticky, replies, createddate, updateddate FROM ".$nn_schema.".forumpost", $runQueries);

	convertTable($db, $nZEDB_schema, "genres", "INSERT INTO ".$nZEDB_schema.".genres (title, type) ".
		"SELECT title, type FROM ".$nn_schema.".genres", $runQueries);

	convertTable($db, $nZEDB_schema, "groups", "INSERT INTO ".$nZEDB_schema.".groups (active, backfill_target, description, id, minfilestoformrelease, minsizetoformrelease, name) ".
		"SELECT active, backfill_target, description, ID, minfilestoformrelease, minsizetoformrelease, name FROM ".$nn_schema.".groups", $runQueries);

	echo "Skipping menu table: menu layouts are different\n";

	convertTable($db, $nZEDB_schema, "movieinfo", "INSERT INTO ".$nZEDB_schema.".movieinfo (actors, backdrop, cover, createddate, director, genre, id, imdbid, language, plot, rating, tagline, title, tmdbid, updateddate, year) ".
		"SELECT actors, backdrop, cover, createddate, director, genre, ID, imdbID, language, plot, rating, tagline, title, tmdbID, updateddate, year FROM ".$nn_schema.".movieinfo", $runQueries);

	runQuery($db, "ALTER TABLE ".$nZEDB_schema.".movieinfo MODIFY column genre varchar(255) NOT NULL", $runQueries);

	convertTable($db, $nZEDB_schema, "musicinfo", "INSERT INTO ".$nZEDB_schema.".musicinfo (artist, asin, cover, createddate, genreid, id, publisher, releasedate, review, salesrank, title, tracks, updateddate, url, year) ".
		"SELECT artist, asin, cover, createddate, genreID, ID, publisher, releasedate, review, salesrank, title, tracks, updateddate, url, year FROM ".$nn_schema.".musicinfo", $runQueries);

	echo "Skipping partrepair table: No coversion needed due to nZEDb changes\n";
	echo "Skipping parts table: No coversion needed due to nZEDb changes\n";
	echo "Skipping predb table: Not in NZEDb\n";

	convertTable($db, $nZEDB_schema, "releaseaudio", "INSERT INTO ".$nZEDB_schema.".releaseaudio (releaseid, audioid, audioformat, audiomode, audiobitratemode, audiobitrate, audiochannels, audiosamplerate, audiolibrary, audiolanguage, audiotitle) ".
		"SELECT releaseID, audioID, audioformat, audiomode, audiobitratemode, audiobitrate, audiochannels, audiosamplerate, audiolibrary, audiolanguage, audiotitle FROM ".$nn_schema.".releaseaudio", $runQueries);

	// You lose all spotnab additions (sourceID, gid, cid, isvisible, issynced, username).
	convertTable($db, $nZEDB_schema, "releasecomment", "INSERT INTO ".$nZEDB_schema.".releasecomment (createddate, host, id, releaseid, text, userid) ".
		"SELECT createddate, host, ID, releaseID, text, userID FROM ".$nn_schema.".releasecomment", $runQueries);

	convertTable($db, $nZEDB_schema, "releaseextrafull", "INSERT INTO ".$nZEDB_schema.".releaseextrafull (releaseid, mediainfo) ".
		"SELECT releaseID, mediainfo FROM ".$nn_schema.".releaseextrafull", $runQueries);

	convertTable($db, $nZEDB_schema, "releasefiles", "INSERT INTO ".$nZEDB_schema.".releasefiles (releaseid, name, size, createddate, passworded) ".
		"SELECT releaseID, name, size, createddate, passworded FROM ".$nn_schema.".releasefiles", $runQueries);

	convertTable($db, $nZEDB_schema, "releasenfo", "INSERT INTO ".$nZEDB_schema.".releasenfo (releaseid, nfo) ".
		"SELECT releaseID, nfo FROM ".$nn_schema.".releasenfo", $runQueries);

	echo "Skipping releaseregex table: Not needed with nZEDb\n";

	// Fix Category
	// nn+ Parent Categories	nZEDb Parent Categories
	// 	ID		Name			Name
	//	1000	Console			Console
	//	2000	Movies			Movies
	//	3000	Audio			Audio
	//	4000	PC				PC
	//	5000	TV				TV
	//	6000	XXX				XXX
	//	*7000	Books			Other
	//	*8000	Misc			Books

	//	name				nn+			nZEDb
	//	Comics				7030/7000	8020/8000
	//	EBook				7020/7000	8010/8000
	//	Mags				7010/7000	8030/8000
	//	Pack				6050/6000	6070/6000
	//	Misc				0			7010/7000
	//	Other				0			7000/null
	//	Other				0			1090/1000
	//  Other				0			3050/3000
	//	3D					2060/2000	2050/2000
	//	BluRay				2050/2000	2060/2000
	//	Books				7000/null	8000/null
	//	Other				6070/6000	6050/6000
	//	Other				8010/8000	8050/8000

	convertTable($db, $nZEDB_schema, "releases", "INSERT INTO ".$nZEDB_schema.".releases (adddate, anidbid, bookinfoid, categoryid, comments, completion, consoleinfoid, episode, fromname, grabs, groupid, guid, haspreview, id, imdbid, musicinfoid, name, passwordstatus, postdate, rageid, rarinnerfilecount, searchname, season, seriesfull, size, totalpart, tvairdate, tvtitle, nzb_guid) ".
		"SELECT adddate, anidbID, bookinfoID, case categoryID when 7030 then 8020 when 7020 then 8010 when 7010 then 8030 when 6050 then 6070 when 2060 then 2050 when 2050 then 2060 when 7000 then 8000 when 6070 then 6050 when 8010 then 8050 else categoryID end, comments, completion, consoleinfoID, episode, fromname, grabs, groupID, guid, haspreview, ID, imdbID, musicinfoID, name, passwordstatus, postdate, rageID, rarinnerfilecount, searchname, season, seriesfull, size, totalpart, tvairdate, tvtitle, gid FROM ".$nn_schema.".releases", $runQueries);

	convertTable($db, $nZEDB_schema, "releasesubs", "INSERT INTO ".$nZEDB_schema.".releasesubs (releaseid, subsid, subslanguage) ".
		"SELECT releaseID, subsID, subslanguage FROM ".$nn_schema.".releasesubs", $runQueries);

	// You lose (definition).
	convertTable($db, $nZEDB_schema, "releasevideo", "INSERT INTO ".$nZEDB_schema.".releasevideo (containerformat,overallbitrate,releaseid,videoaspect,videocodec,videoduration,videoformat,videoframerate,videoheight,videolibrary,videowidth) ".
		"SELECT containerformat,overallbitrate,releaseID,videoaspect,videocodec,videoduration,videoformat,videoframerate, videoheight, videolibrary, videowidth FROM ".$nn_schema.".releasevideo", $runQueries);

	echo "Skipping rolexcat table: Not in nZEDb\n";

	echo "Skipping sites table: You must manually update your site settings (siteseed is not used)\n";

	echo "Skipping sphinx table: Not in nZEDb\n";

	echo "Skipping spotnabsource table: Not in nZEDb\n";

	echo "Skipping thetvdb table: Not in nZEDb\n";

	convertTable($db, $nZEDB_schema, "tvrage", "INSERT INTO ".$nZEDB_schema.".tvrage (country, createddate, description, genre, id, imgdata, nextdate, nextinfo, prevdate, previnfo, rageid, releasetitle, tvdbid) ".
		"SELECT country, createddate, description, genre, ID, imgdata, nextdate, nextinfo, prevdate, previnfo, rageID, releasetitle, tvdbID FROM ".$nn_schema.".tvrage", $runQueries);

	convertTable($db, $nZEDB_schema, "upcoming", "INSERT INTO ".$nZEDB_schema.".upcoming (source, typeid, info, updateddate) ".
		"SELECT source, typeID, info, updateddate FROM ".$nn_schema.".upcoming", $runQueries);

	convertTable($db, $nZEDB_schema, "usercart", "INSERT INTO ".$nZEDB_schema.".usercart (userid, releaseid, createddate) ".
		"SELECT userID, releaseID, createddate FROM ".$nn_schema.".usercart", $runQueries);

	// You lose (hosthash, releaseID).
	convertTable($db, $nZEDB_schema, "userdownloads", "INSERT INTO ".$nZEDB_schema.".userdownloads (id, timestamp, userid) ".
		"SELECT ID, timestamp, userID FROM ".$nn_schema.".userdownloads", $runQueries);

	convertTable($db, $nZEDB_schema, "userexcat", "INSERT INTO ".$nZEDB_schema.".userexcat (userid, categoryid, createddate) ".
		"SELECT userID, categoryID, createddate FROM ".$nn_schema.".userexcat", $runQueries);

	convertTable($db, $nZEDB_schema, "userinvite", "INSERT INTO ".$nZEDB_schema.".userinvite (guid, userid, createddate) ".
		"SELECT guid, userID, createddate FROM ".$nn_schema.".userinvite", $runQueries);

	convertTable($db, $nZEDB_schema, "usermovies", "INSERT INTO ".$nZEDB_schema.".usermovies (userid, imdbid, categoryid, createddate) ".
		"SELECT userID, imdbID, categoryID, createddate FROM ".$nn_schema.".usermovies", $runQueries);

	// You lose (hosthash).
	convertTable($db, $nZEDB_schema, "userrequests", "INSERT INTO ".$nZEDB_schema.".userrequests (id, request, timestamp, userid) ".
		"SELECT ID, request, timestamp, userID FROM ".$nn_schema.".userrequests", $runQueries);

	// You lose (canpre).
	convertTable($db, $nZEDB_schema, "userroles", "INSERT IGNORE INTO ".$nZEDB_schema.".userroles (apirequests, canpreview, defaultinvites, downloadrequests, id, isdefault, name) ".
				"SELECT apirequests, canpreview, defaultinvites, downloadrequests, ID, isdefault, name FROM ".$nn_schema.".userroles", $runQueries);

	// You lose (kindleid, notes, rolechangedate, nzbvortex_api_key, nzbvortex_server_url, siteseed; and password IS reset).
	convertTable($db, $nZEDB_schema, "users", "INSERT INTO ".$nZEDB_schema.".users (apiaccess, bookview, consoleview, createddate, email, grabs, host, id, invitedby, invites, lastlogin, movieview, musicview, password, resetguid, role, rsstoken, sabapikey, sabapikeytype, sabpriority, saburl, username) ".
		"SELECT apiaccess, bookview, consoleview, createddate, email, grabs, host, ID, invitedby, invites, lastlogin, movieview, musicview, NULL, resetguid, role, rsstoken, sabapikey, sabapikeytype, sabpriority, saburl, username FROM ".$nn_schema.".users", $runQueries);

	convertTable($db, $nZEDB_schema, "userseries", "INSERT INTO ".$nZEDB_schema.".userseries (userid, rageid, categoryid, createddate) ".
		"SELECT userID, rageID, categoryID, createddate FROM ".$nn_schema.".userseries", $runQueries);

	exit("Due to some issues moving roles we've used INSERT IGNORE... Please check your user roles in your nZEDb install\n"
		."You now need to run copy_from_newznab.php to copy nzbs, covers, previews, set the nzbstatus and nzb path level\n\n"
		."DO NOT run update_releases.php before running copy_from_newznab.php, you will have to start over.\n");
?>
