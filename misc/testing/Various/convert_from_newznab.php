<?php
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\Settings;

// Check argument count.
if (!isset($argv[1]) || !isset($argv[2]) || !isset($argv[3])) {
	passthru("clear");
	echo "Usage: newznab_schema nZEDB_schema true/false\n"
		 . "example: php convert_from_newznab.php newznab nzedb true\n\n"
		 .
		 "newznab_schema: Schema where your newznab install is located. The database name. The current user in your config.php file must have access to this schema\n"
		 .
		 "nZEDB_schema: Schema where you want the newznab data converted too. The database name. The schema must be populated and will be wiped clean except the sites and categories tables\n"
		 .
		 "true/false: false = Show the queries but do not run.  true means you understand the risks and want to convert the data (Your old data will not be touched\n\n"
		 .
		 "NOTE: This is experimental and there is a possibility that this will not work correctly.  Please let us know if it doesn't work correctly, but we are not responsible for any lost data.\n"
		 .
		 "      You will have to start any backfilling and processing over again since we use a different mechanism for processing releases\n\n";
	exit(1);
}

$pdo = new Settings();

function runQuery($pdo, $sql, $runQueries)
{
	if ($runQueries) {
		$pdo->queryDirect($sql);
		return true;
	} else {
		echo $sql . ";\n";
		return true;
	}
}

function runQueryupdate($pdo, $sql, $runQueries)
{
	if ($runQueries) {
		$pdo->queryExec($sql);
		return true;
	} else {
		echo $sql . ";\n";
		return true;
	}
}

function convertTable($pdo, $nZEDbschema, $tableName, $sql, $runQueries)
{
	if ($runQueries) {
		echo "Converting: " . $tableName . " ";
	}

	$renameSQL = "RENAME TABLE " . $nZEDbschema . "." . $tableName . " TO " . $nZEDbschema .
				 ".original_" . $tableName;
	$createSQL = "CREATE TABLE " . $nZEDbschema . "." . $tableName . " LIKE " . $nZEDbschema .
				 ".original_" . $tableName;

	if (!runQuery($pdo, $renameSQL, $runQueries)) {
		exit("Unable to rename table Running the following SQL: " . $renameSQL .
			 "\nConversion Stopped....\n");
	}

	if (!runQuery($pdo, $createSQL, $runQueries)) {
		exit("Unable to create table Running the following SQL: " . $createSQL .
			 "\nConversion Stopped....\n");
	}

	if (!runQuery($pdo, $sql, $runQueries)) {
		die("Unable to move the data... Running: " . $sql . "\nConversion Stopped....\n");
	}

	if ($runQueries) {
		echo "Done.\n";
	}
}

function truncateTable($pdo, $tableName, $runQueries)
{
	if (!runQuery($pdo, "TRUNCATE TABLE " . $tableName, $runQueries)) {
		exit("Unable to truncate: " . $tableName . "\nConversion Stopped....\n");
	}
}

$nn_schema = $argv[1];
$nZEDB_schema = $argv[2];
$runQueries = $argv[3] == "true";

// This converts from the newznab schema to the nZEDb schema.

echo "Resetting Collections/Binaries/Parts/PartRepair\n";
truncateTable($pdo, $nZEDB_schema . ".collections", $runQueries);
truncateTable($pdo, $nZEDB_schema . ".binaries", $runQueries);
truncateTable($pdo, $nZEDB_schema . ".parts", $runQueries);
truncateTable($pdo, $nZEDB_schema . ".missed_parts", $runQueries);

echo "Converting from newznab to nZEDb... This will take a while...\n\n";

convertTable($pdo,
			 $nZEDB_schema,
			 "anidb",
			 "INSERT INTO " . $nZEDB_schema .
			 ".anidb (airdates, anidbid, categories, characters, creators, description, enddate, episodetitles, epnos, picture, rating, related, startdate, title, type, unixtime) " .
			 "SELECT airdates, anidbID, categories, characters, creators, description, enddate, episodetitles, epnos, picture, rating, related, startdate, title, type, UNIX_TIMESTAMP(createddate) FROM " .
			 $nn_schema . ".anidb",
			 $runQueries);
/* we no longer have this table, so this has to be changed to match current practise.
convertTable($pdo,
			 $nZEDB_schema,
			 "animetitles",
			 "INSERT INTO " . $nZEDB_schema . ".animetitles (anidbid, title, unixtime) " .
			 "SELECT anidbID, title, UNIX_TIMESTAMP(createddate) FROM " . $nn_schema .
			 ".animetitles",
			 $runQueries);
*/
echo "Skipping binaries table: Different in nZEDb\n";

convertTable($pdo,
			 $nZEDB_schema,
			 "binaryblacklist",
			 "INSERT INTO " . $nZEDB_schema .
			 ".binaryblacklist (id, groupname, regex, msgcol, optype, status, description) " .
			 "SELECT ID, groupname, regex, msgcol, optype, status, description FROM " . $nn_schema .
			 ".binaryblacklist",
			 $runQueries);

// You lose genreID and dewey.
convertTable($pdo,
			 $nZEDB_schema,
			 "bookinfo",
			 "INSERT INTO " . $nZEDB_schema .
			 ".bookinfo (id, title , author, asin, isbn, ean, url, publisher, publishdate, pages, overview, cover, createddate, updateddate) " .
			 "SELECT ID, title , author, asin, isbn, ean, url, publisher, publishdate, pages, review, cover, createddate, updateddate FROM " .
			 $nn_schema . ".bookinfo GROUP BY asin",
			 $runQueries);

echo "Skipping category table: using nZEDb's version\n";

convertTable($pdo,
			 $nZEDB_schema,
			 "consoleinfo",
			 "INSERT INTO " . $nZEDB_schema .
			 ".consoleinfo (asin, cover, createddate, esrb, genreid, id, platform, publisher, releasedate, review, salesrank, title, updateddate, url) " .
			 "SELECT asin, cover, createddate, esrb, genreID, ID, platform, publisher, releasedate, review, salesrank, title, updateddate, url FROM " .
			 $nn_schema . ".consoleinfo GROUP BY asin",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "page_contents",
			 "INSERT INTO " . $nZEDB_schema .
			 ".page_contents (id, title, url, body, metadescription, metakeywords, contenttype, showinmenu, status, ordinal, role) " .
			 "SELECT id, title, url, body, metadescription, metakeywords, contenttype, showinmenu, status, ordinal, role FROM " .
			 $nn_schema . ".content",
			 $runQueries);

echo "episodeinfo = tvrage_episodes in nZEDb\n";
// Convert episodeinfo to tvrageepisodes - You loose (tvdbID, imdbID, director, gueststars, overview, rating, writer, epabsolute)
convertTable($pdo,
			 $nZEDB_schema,
			 "tvrage_episodes",
			 "INSERT INTO " . $nZEDB_schema .
			 ".tvrage_episodes (id, rageid, showtitle, airdate, link, fullep, eptitle) " .
			 "SELECT MIN(ID), rageID, showtitle, MIN(airdate), link, fullep, eptitle FROM " .
			 $nn_schema . ".episodeinfo where rageID <> 0 GROUP BY rageID, fullep",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "forum_posts",
			 "INSERT INTO " . $nZEDB_schema .
			 ".forum_posts (forumid, parentid, user_id, subject, message, locked, sticky, replies, createddate, updateddate) " .
			 "SELECT forumID, parentID, userID, subject, message, locked, sticky, replies, createddate, updateddate FROM " .
			 $nn_schema . ".forumpost",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "genres",
			 "INSERT INTO " . $nZEDB_schema . ".genres (title, type) " .
			 "SELECT title, type FROM " . $nn_schema . ".genres",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "groups",
			 "INSERT INTO " . $nZEDB_schema .
			 ".groups (active, backfill_target, description, id, minfilestoformrelease, minsizetoformrelease, name) " .
			 "SELECT active, backfill_target, description, ID, minfilestoformrelease, minsizetoformrelease, name FROM " .
			 $nn_schema . ".groups",
			 $runQueries);

echo "Skipping menu table: menu layouts are different\n";

convertTable($pdo,
			 $nZEDB_schema,
			 "movieinfo",
			 "INSERT INTO " . $nZEDB_schema .
			 ".movieinfo (actors, backdrop, cover, createddate, director, genre, id, imdbid, language, plot, rating, tagline, title, tmdbid, updateddate, year) " .
			 "SELECT actors, backdrop, cover, createddate, director, genre, ID, imdbID, language, plot, rating, tagline, title, tmdbID, updateddate, year FROM " .
			 $nn_schema . ".movieinfo",
			 $runQueries);

runQuery($pdo,
		 "ALTER TABLE " . $nZEDB_schema . ".movieinfo MODIFY column genre varchar(255) NOT NULL",
		 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "musicinfo",
			 "INSERT INTO " . $nZEDB_schema .
			 ".musicinfo (artist, asin, cover, createddate, genreid, id, publisher, releasedate, review, salesrank, title, tracks, updateddate, url, year) " .
			 "SELECT artist, asin, cover, createddate, genreID, ID, publisher, releasedate, review, salesrank, title, tracks, updateddate, url, year FROM " .
			 $nn_schema . ".musicinfo GROUP BY asin",
			 $runQueries);

echo "Skipping partrepair table: No conversion needed due to nZEDb changes\n";
echo "Skipping parts table: No conversion needed due to nZEDb changes\n";
echo "Skipping predb table: Not in NZEDb\n";

convertTable($pdo,
			 $nZEDB_schema,
			 "audio_data",
			 "INSERT INTO " . $nZEDB_schema .
			 ".audio_data (releaseid, audioid, audioformat, audiomode, audiobitratemode, audiobitrate, audiochannels, audiosamplerate, audiolibrary, audiolanguage, audiotitle) " .
			 "SELECT releaseID, audioID, audioformat, audiomode, audiobitratemode, audiobitrate, audiochannels, audiosamplerate, audiolibrary, audiolanguage, audiotitle FROM " .
			 $nn_schema . ".releaseaudio GROUP BY releaseID",
			 $runQueries);

// You lose all spotnab additions (sourceID, gid, cid, isvisible, issynced, username).
convertTable($pdo,
			 $nZEDB_schema,
			 "release_comments",
			 "INSERT INTO " . $nZEDB_schema .
			 ".release_comments (createddate, host, id, releaseid, text, user_id) " .
			 "SELECT createddate, host, ID, releaseID, text, userID FROM " . $nn_schema .
			 ".releasecomment",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "releaseextrafull",
			 "INSERT INTO " . $nZEDB_schema . ".releaseextrafull (releaseid, mediainfo) " .
			 "SELECT releaseID, mediainfo FROM " . $nn_schema . ".releaseextrafull",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "release_files",
			 "INSERT INTO " . $nZEDB_schema .
			 ".release_files (releaseid, name, size, createddate, passworded) " .
			 "SELECT releaseID, name, size, createddate, passworded FROM " . $nn_schema .
			 ".releasefiles group by releaseID",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "release_nfos",
			 "INSERT INTO " . $nZEDB_schema . ".release_nfos (releaseid, nfo) " .
			 "SELECT releaseID, nfo FROM " . $nn_schema . ".releasenfo group by releaseID",
			 $runQueries);

echo "Skipping releaseregex table: Not needed with nZEDb\n";

// Fix Category
// nn+ Parent Categories        nZEDb Parent Categories
//      ID              Name                    Name
//      1000    Console                 Console
//      2000    Movies                  Movies
//      3000    Audio                   Audio
//      4000    PC                              PC
//      5000    TV                              TV
//      6000    XXX                             XXX
//      *7000   Books                   Other
//      *8000   Misc                    Books

//      name                            nn+                     nZEDb
//      Comics                          7030/7000       8020/8000
//      EBook                           7020/7000       8010/8000
//      Mags                            7010/7000       8030/8000
//      Pack                            6050/6000       6070/6000
//      Misc                            0                       7010/7000
//      Other                           0                       7000/null
//      Other                           0                       1090/1000
//  Other                               0                       3050/3000
//      3D                                      2060/2000       2050/2000
//      BluRay                          2050/2000       2060/2000
//      Books                           7000/null       8000/null
//      Other                           6070/6000       6050/6000
//      Other                           8010/8000       8050/8000

convertTable($pdo,
			 $nZEDB_schema,
			 "releases",
			 "INSERT INTO " . $nZEDB_schema .
			 ".releases (adddate, anidbid, bookinfoid, categoryid, comments, completion, consoleinfoid, episode, fromname, grabs, group_id, guid, haspreview, id, imdbid, musicinfoid, name, passwordstatus, postdate, rageid, rarinnerfilecount, searchname, season, seriesfull, size, totalpart, tvairdate, tvtitle, nzb_guid) " .
			 "SELECT adddate, anidbID, bookinfoID, case categoryID when 7030 then 8020 when 7020 then 8010 when 7010 then 8030 when 6050 then 6070 when 2060 then 2050 when 2050 then 2060 when 7000 then 8000 when 6070 then 6050 when 8010 then 8050 else categoryID end, comments, completion, consoleinfoID, episode, fromname, grabs, group_id, guid, haspreview, ID, imdbID, musicinfoID, name, passwordstatus, postdate, rageID, rarinnerfilecount, searchname, season, seriesfull, size, totalpart, tvairdate, tvtitle, gid FROM " .
			 $nn_schema . ".releases",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "release_subtitles",
			 "INSERT INTO " . $nZEDB_schema . ".release_subtitles (releaseid, subsid, subslanguage) " .
			 "SELECT releaseID, subsID, subslanguage FROM " . $nn_schema . ".releasesubs",
			 $runQueries);

// You lose (definition).
convertTable($pdo,
			 $nZEDB_schema,
			 "video_data",
			 "INSERT INTO " . $nZEDB_schema .
			 ".video_data (containerformat,overallbitrate,releaseid,videoaspect,videocodec,videoduration,videoformat,videoframerate,videoheight,videolibrary,videowidth) " .
			 "SELECT containerformat,overallbitrate,releaseID,videoaspect,videocodec,videoduration,videoformat,videoframerate, videoheight, videolibrary, videowidth FROM " .
			 $nn_schema . ".releasevideo group by releaseID",
			 $runQueries);

echo "Skipping rolexcat table: Not in nZEDb\n";

echo "Skipping sites table: You must manually update your site settings (siteseed is not used)\n";

echo "Skipping sphinx table: Not in nZEDb\n";

echo "Skipping spotnabsource table: Not in nZEDb\n";

echo "Skipping thetvdb table: Not in nZEDb\n";

convertTable($pdo,
			 $nZEDB_schema,
			 "tvrage_titles",
			 "INSERT INTO " . $nZEDB_schema .
			 ".tvrage_titles (country, createddate, description, genre, id, imgdata, nextdate, nextinfo, prevdate, previnfo, rageid, releasetitle, tvdbid) " .
			 "SELECT country, createddate, description, genre, ID, imgdata, nextdate, nextinfo, prevdate, previnfo, rageID, releasetitle, tvdbID FROM " .
			 $nn_schema . ".tvrage group by releasetitle",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "upcoming_releases",
			 "INSERT INTO " . $nZEDB_schema . ".upcoming_releases (source, typeid, info, updateddate) " .
			 "SELECT source, typeID, info, updateddate FROM " . $nn_schema . ".upcoming",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "users_releases",
			 "INSERT INTO " . $nZEDB_schema . ".users_releases (user_id, releaseid, createddate) " .
			 "SELECT userID, releaseID, createddate FROM " . $nn_schema . ".usercart",
			 $runQueries);

// You lose (hosthash, releaseID).
convertTable($pdo,
			 $nZEDB_schema,
			 "user_downloads",
			 "INSERT INTO " . $nZEDB_schema . ".user_downloads (id, timestamp, user_id) " .
			 "SELECT ID, timestamp, userID FROM " . $nn_schema . ".userdownloads",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "user_excluded_categories",
			 "INSERT INTO " . $nZEDB_schema . ".user_excluded_categories (user_id, categoryid, createddate) " .
			 "SELECT userID, categoryID, createddate FROM " . $nn_schema . ".userexcat",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "invitations",
			 "INSERT INTO " . $nZEDB_schema . ".invitations (guid, user_id, createddate) " .
			 "SELECT guid, userID, createddate FROM " . $nn_schema . ".userinvite",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "user_movies",
			 "INSERT INTO " . $nZEDB_schema .
			 ".user_movies (user_id, imdbid, categoryid, createddate) " .
			 "SELECT userID, imdbID, categoryID, createddate FROM " . $nn_schema . ".usermovies",
			 $runQueries);

// You lose (hosthash).
convertTable($pdo,
			 $nZEDB_schema,
			 "user_requests",
			 "INSERT INTO " . $nZEDB_schema . ".user_requests (id, request, timestamp, user_id) " .
			 "SELECT ID, request, timestamp, userID FROM " . $nn_schema . ".userrequests",
			 $runQueries);

// You lose (canpre).
convertTable($pdo,
			 $nZEDB_schema,
			 "user_roles",
			 "INSERT IGNORE INTO " . $nZEDB_schema .
			 ".user_roles (apirequests, canpreview, defaultinvites, downloadrequests, id, isdefault, name) " .
			 "SELECT apirequests, canpreview, defaultinvites, downloadrequests, ID, isdefault, name FROM " .
			 $nn_schema . ".userroles",
			 $runQueries);

// You lose (kindleid, notes, rolechangedate, nzbvortex_api_key, nzbvortex_server_url, siteseed; and password IS reset).
convertTable($pdo,
			 $nZEDB_schema,
			 "users",
			 "INSERT INTO " . $nZEDB_schema .
			 ".users (apiaccess, bookview, consoleview, createddate, email, grabs, host, id, invitedby, invites, lastlogin, movieview, musicview, password, resetguid, role, rsstoken, sabapikey, sabapikeytype, sabpriority, saburl, username) " .
			 "SELECT apiaccess, bookview, consoleview, createddate, email, grabs, host, ID, invitedby, invites, lastlogin, movieview, musicview, NULL, resetguid, role, rsstoken, sabapikey, sabapikeytype, sabpriority, saburl, username FROM " .
			 $nn_schema . ".users",
			 $runQueries);

convertTable($pdo,
			 $nZEDB_schema,
			 "user_series",
			 "INSERT INTO " . $nZEDB_schema .
			 ".user_series (user_id, rageid, categoryid, createddate) " .
			 "SELECT userID, rageID, categoryID, createddate FROM " . $nn_schema . ".userseries",
			 $runQueries);

exit("Due to some issues moving roles we've used INSERT IGNORE... Please check your user roles in your nZEDb install\n"
	 .
	 "You now need to run copy_from_newznab.php to copy nzbs, covers, previews, set the nzbstatus and nzb path level\n\n"
	 .
	 "DO NOT run update_releases.php before running copy_from_newznab.php, you will have to start over.\n");
?>
