<?php
passthru('clear');
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'ColorCLI.php';
$c = new ColorCLI;

echo $c->warning("This script removes all releases, nzb files, samples, previews , nfos, truncates all article tables and resets all groups.");
echo $c->header("Are you sure you want reset the DB?  Type 'DESTROY' to continue:  \n");
echo $c->warningOver("\n");
$line = fgets(STDIN);
if(trim($line) != 'DESTROY')
	exit($c->error("This script is dangerous you must type DESTROY for it function."));

echo "\n";
echo $c->header("Thank you, continuing...\n\n");

require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'releases.php';
require_once nZEDb_LIB . 'site.php';
require_once nZEDb_LIB . 'consoletools.php';
require_once nZEDb_LIB . 'releaseimage.php';
require_once nZEDb_LIB . 'nzb.php';

$db = new Db();
$s = new Sites();
$site = $s->get();
$timestart = TIME();
$relcount = 0;
$ri = new ReleaseImage();
$nzb = new NZB();

$db->queryExec("UPDATE groups SET first_record = 0, first_record_postdate = NULL, last_record = 0, last_record_postdate = NULL, last_updated = NULL");
echo $c->primary("Reseting all groups completed.");

$arr = array("tvrage", "releasenfo", "releasecomment", "usercart", "usermovies", "userseries", "movieinfo", "musicinfo", "releasefiles", "releaseaudio", "releasesubs", "releasevideo", "releaseextrafull", "parts", "partrepair", "binaries", "collections", "nzbs");
foreach ($arr as &$value)
{
	$rel = $db->queryExec("TRUNCATE TABLE $value");
	if($rel !== false)
		echo $c->primary("Truncating ${value} completed.");
}
unset($value);

$sql = "SHOW table status";
$tables = $db->query($sql);
foreach($tables as $row)
{
	$tbl = $row['name'];
	if (preg_match('/\d+_collections/',$tbl) || preg_match('/\d+_binaries/',$tbl) || preg_match('/\d+_parts/',$tbl))
	{
		$rel = $db->queryDirect(sprintf('DROP TABLE %s', $tbl));
		if($rel !== false)
			echo $c->primary("Dropping ${tbl} completed.");
	}
}

echo $c->header("Querying db for releases.");
$relids = $db->queryDirect("SELECT id, guid, imdbid FROM releases ORDER BY postdate DESC");
if ($relids->rowCount() > 0)
{
	echo $c->primary("Deleting ".number_format($relids->rowCount())." releases, NZB's, covers, previews and samples.");
	$releases = new Releases();
	$consoletools = new ConsoleTools();
	foreach ($relids as $relid)
	{
		@unlink($nzb->getNZBPath($relid['guid'], $site->nzbpath, false, $site->nzbsplitlevel));
		@unlink($ri->delete($relid['guid'], $relid['imdbid']));
		$consoletools->overWrite($c->headerOver("Deleting: ".$consoletools->percentString(++$relcount,$relids->rowCount())." Time:".$consoletools->convertTimer(TIME() - $timestart)));
	}
}

$rel = $db->queryExec("TRUNCATE TABLE releases");
if($rel !== false)
	echo $c->primary("\nTruncating releases completed.");

echo $c->header("Getting List of nzbs to check against db.");
$dirItr = new RecursiveDirectoryIterator($site->nzbpath);
$itr = new RecursiveIteratorIterator($dirItr, RecursiveIteratorIterator::LEAVES_ONLY);
$consoletools = new ConsoleTools();
$deleted = 0;
foreach ($itr as $filePath)
{
	@unlink($filePath);
	$consoletools->overWrite($c->header("Deleting NZBs: ".++$deleted." deleted from disk,  Running time: ".$consoletools->convertTimer(TIME() - $timestart)));
}

echo $c->header("\nGetting List of Images, previews and samples that still remain.");
$dirItr = new RecursiveDirectoryIterator(nZEDb_WWW . 'covers');
$itr = new RecursiveIteratorIterator($dirItr, RecursiveIteratorIterator::LEAVES_ONLY);
$consoletools = new ConsoleTools();
$deleted = 0;
foreach ($itr as $filePath)
{
	if (basename($filePath) != '.gitignore' && basename($filePath) != 'no-cover.jpg' && basename($filePath) != 'no-backdrop.jpg')
	{
		@unlink($filePath);
		$consoletools->overWrite($c->headerOver("Deleting Files: ".++$deleted." deleted from disk,  Running time: ".$consoletools->convertTimer(TIME() - $timestart)));
	}
}

echo $c->header("\nGetting Updated List of TV Shows from TVRage.");
$tvshows = @simplexml_load_file('http://services.tvrage.com/feeds/show_list.php');
if ($tvshows !== false)
{
	foreach ($tvshows->show as $rage)
	{
		if (isset($rage->id) && isset($rage->name) && !empty($rage->id) && !empty($rage->name))
			$db->queryInsert(sprintf('INSERT INTO tvrage (rageid, releasetitle, country) VALUES (%s, %s, %s)', $db->escapeString($rage->id), $db->escapeString($rage->name), $db->escapeString($rage->country)));
	}
}
else
	echo $c->error("TVRage site has a hard limit of 400 concurrent api requests. At the moment, they have reached that limit. Please wait before retrying agrain.");

if ($relcount > 0)
{
	$consoletools = new ConsoleTools();
	echo $c->header("\nDeleted ".$relcount." release(s). This script ran for ".$consoletools->convertTime(TIME() - $timestart));
}
