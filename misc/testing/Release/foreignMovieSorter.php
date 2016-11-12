<?php
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nzedb\Category;
use nzedb\Categorize;
use nzedb\db\DB;

$pdo = new DB();
$categorize = new Categorize(['Settings' => $pdo]);

if (isset($argv[1]) && $argv[1] === "true") {
	$results = getForeignMovies();
	foreach ($results as $result) {
		$cat = determineMovieCategory($result['searchname']);
		echo $pdo->log->headerOver("English track found for: ") . $pdo->log->primary($result['searchname'] . ": " . $cat . " moving...");
		updaterelease($result['id'], $cat);
	}
} else {
	exit($pdo->log->error("\nThis script attempts to recategorize foreign movies that have an english audio track.\n"
					. "php $argv[0] true       ...:recategorize foreign movies.\n"));
}

function getForeignMovies()
{
	global $pdo;
	$like = 'LIKE';
	return $pdo->query('SELECT r.id, r.searchname FROM releases r JOIN audio_data ra ON ra.releases_id = r.id WHERE ra.audiolanguage ' . $like . " '%English%' AND r.categories_id = 2010");
}

function updateRelease($id, $cat)
{
	global $pdo;
	$pdo->queryExec(sprintf("UPDATE releases SET categories_id = %s WHERE id = %d", $cat, $id));
}

function determineMovieCategory($name)
{
	// Determine sub category
	global $categorize;

	if ($categorize->isMovieSD($name)) {
		return Category::MOVIE_SD;
	}

	if ($categorize->isMovie3D($name)) {
		return Category::MOVIE_3D;
	}

	if ($categorize->isMovieHD($name)) {
		return Category::MOVIE_HD;
	}

	if ($categorize->isMovieBluRay($name)) {
		return Category::MOVIE_BLURAY;
	}

	// Hack to catch 1080 named releases that didnt reveal their encoding.
	if (strrpos($name, '1080') != false) {
		return Category::MOVIE_HD;
	}

	// Hack to catch 720 named releases that didnt reveal their encoding.
	if (strrpos($name, '720') != false) {
		return Category::MOVIE_HD;
	}

	return Category::MOVIE_OTHER;
}
