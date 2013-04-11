<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/music.php");
require_once(FS_ROOT."/../../www/lib/category.php");

$music = new Music(true);
$db = new Db;

$limit = 10;
$testSpecificRel = 'Joy - Touch By Touch 2011-CDM-2010-RQS'; //make this string empty to check last $limit releases


if (empty($testSpecificRel)) {
	$res = $db->queryDirect(sprintf("SELECT searchname, ID from releases where musicinfoID IS NULL and categoryID in ( select ID from category where parentID = %d ) ORDER BY id DESC LIMIT %d", Category::CAT_PARENT_MUSIC, $limit));
	foreach ($res as $album) {
		$artist = $music->parseArtist($album);
		doTest($artist);
	}
} else {
	$artist = $music->parseArtist($testSpecificRel);
	doTest($artist);
}

function doTest($relInfo) {
	echo $relInfo['releasename'].'<br />';
	$result = updateMusicInfo($relInfo['artist'], $relInfo['album'], $relInfo['year']);
	if ($result !== false) {
		echo '<pre>';
		print_r($result);
		echo '</pre><br /><br />';
	}
}


function updateMusicInfo($artist, $album, $year)
	{
		$db = new DB();
		$gen = new Genres();
		$music = new Music();
		
		$mus = array();
		$amaz = $music->fetchAmazonProperties($artist." - ".$album);
		if (!$amaz) 
			return false;
		
		//load genres
		$defaultGenres = $gen->getGenres(Genres::MUSIC_TYPE);
		$genreassoc = array();
		foreach($defaultGenres as $dg) {
			$genreassoc[$dg['ID']] = strtolower($dg['title']);
		}		
		
		//
		// get album properties
		//

		$mus['coverurl'] = (string) $amaz->Items->Item->MediumImage->URL;
		if ($mus['coverurl'] != "")
			$mus['cover'] = 1;
		else
			$mus['cover'] = 0;

		$mus['title'] = (string) $amaz->Items->Item->ItemAttributes->Title;
		if (empty($mus['title']))
			$mus['title'] = $album;
			
		$mus['asin'] = (string) $amaz->Items->Item->ASIN;
		
		$mus['url'] = (string) $amaz->Items->Item->DetailPageURL;
		$mus['url'] = str_replace("%26tag%3Dws", "%26tag%3Dopensourceins%2D21", $mus['url']);
		
		$mus['salesrank'] = (string) $amaz->Items->Item->SalesRank;
		if ($mus['salesrank'] == "")
			$mus['salesrank'] = 'null';
		
		$mus['artist'] = (string) $amaz->Items->Item->ItemAttributes->Artist;
		if (empty($mus['artist']))
			$mus['artist'] = $artist;
		
		$mus['publisher'] = (string) $amaz->Items->Item->ItemAttributes->Publisher;
		
		$mus['releasedate'] = $db->escapeString((string) $amaz->Items->Item->ItemAttributes->ReleaseDate);
		if ($mus['releasedate'] == "''")
			$mus['releasedate'] = 'null';
		
		$mus['review'] = "";
		if (isset($amaz->Items->Item->EditorialReviews))
			$mus['review'] = trim(strip_tags((string) $amaz->Items->Item->EditorialReviews->EditorialReview->Content));
		
		$mus['year'] = $year;
		if ($mus['year'] == "" && $mus['releasedate'] != 'null')
			$mus['year'] = substr($mus['releasedate'], 1, 4);
		
		$mus['tracks'] = "";
		if (isset($amaz->Items->Item->Tracks))
		{
			$tmpTracks = (array) $amaz->Items->Item->Tracks->Disc;
			$tracks = $tmpTracks['Track'];
			$mus['tracks'] = (is_array($tracks) && !empty($tracks)) ? implode('|', $tracks) : '';
		}
		
		$genreKey = -1;
		$genreName = '';
		$amazGenres = (array) $amaz->Items->Item->BrowseNodes;
		foreach($amazGenres as $amazGenre) {
			foreach($amazGenre as $ag) {
				$tmpGenre = strtolower( (string) $ag->Name );
				if (!empty($tmpGenre)) {
					if (in_array($tmpGenre, $genreassoc)) {
						$genreKey = array_search($tmpGenre, $genreassoc);
						$genreName = $tmpGenre;
						break;
					} else {
						//we got a genre but its not stored in our genre table
						$genreName = (string) $ag->Name;
						$genreKey = 'new genre to be added';
						//$genreKey = $db->queryInsert(sprintf("INSERT INTO genres (`title`, `type`) VALUES (%s, %d)", $db->escapeString($genreName), Genres::MUSIC_TYPE));
						break;
					}
				}
			}
		}
		$mus['musicgenre'] = $genreName;
		$mus['musicgenreID'] = $genreKey;
		
		$mus['amaz'] = $amaz->Items->Item;
		
		return $mus;
}	
?>