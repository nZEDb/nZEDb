<?php
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/category.php");

function getForeignMovies()
{
	$db = new DB();
	return $db->query("SELECT r.id, r.searchname FROM releases r JOIN releaseaudio ra ON ra.releaseID = r.id WHERE ra.audiolanguage LIKE '%English%' AND r.categoryid = 2010");
}

function updateRelease($id, $cat)
{
	$db = new DB();
	$db->queryExec(sprintf("UPDATE releases SET categoryid = %s WHERE id = %d",  $cat,  $id));
}

function determineMovieCategory($name)
{
	// Determine sub category
	$cat = new Category();

	if($cat->isMovieSD($name))
		return "2030";

	if($cat->isMovie3D($name))
		return "2060";

	if($cat->isMovieHD($name))
		return "2040";

	if($cat->isMovieBluRay($name))
		return "2050";

	// Hack to catch 1080 named releases that didnt reveal their encoding.
	if (strrpos($name,'1080') != false)
		return "2040";

	// Hack to catch 720 named releases that didnt reveal their encoding.
	if (strrpos($name,'720') != false)
		return "2040";

	return "2020";
}

$results = getForeignMovies();

foreach($results as $result)
{
	$cat = determineMovieCategory($result['searchname']);
	echo "English track found for ".$result['searchname']." - ".$cat." moving...\n";
	updaterelease( $result['id'], $cat);
}
