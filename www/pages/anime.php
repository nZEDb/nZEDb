<?php
if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$Releases = new Releases(['Settings' => $page->settings]);
$AniDB = new AniDB(['Settings' => $page->settings]);

if (isset($_GET["id"]) && ctype_digit($_GET['id'])) {

	# force the category to 5070 as it should be for anime, as $catarray was NULL and we know the category for sure for anime
	$release = $Releases->searchbyAnidbId($_GET["id"], '', 0, 1000, "", array("5070"), -1);
	$AniDBAPIArray = $AniDB->getAnimeInfo($_GET['id']);
	if (!$release && !$AniDBAPIArray) {
		$page->show404();
	}

	$animeEpisodeTitle = $postdate = array();
	foreach ($release as $rlk => $rlv) {
		$animeEpisodeTitle[$rlk] = $rlv['tvtitle'];
		$postdate[$rlk] = $rlv['postdate'];
	}

	$animeEpisodeTitles = array();
	foreach ($release as $r) {
		$animeEpisodeTitles[$r['tvtitle']][] = $r;
	}
	$page->smarty->assign('animeEpisodeTitlesSize', count($animeEpisodeTitles));
	$page->smarty->assign('animeEpisodeTitles', $animeEpisodeTitles);
	$page->smarty->assign('animeAnidbID', $AniDBAPIArray['anidbid']);
	# case is off on old variable this resolves that, I do not think the other is ever used, but left if anyways
	$page->smarty->assign('animeAnidbid', $AniDBAPIArray['anidbid']);
	$page->smarty->assign('animeTitle', $AniDBAPIArray['title']);
	$page->smarty->assign('animeType', $AniDBAPIArray['type']);
	$page->smarty->assign('animePicture', $AniDBAPIArray['picture']);
	$page->smarty->assign('animeStartDate', $AniDBAPIArray['startdate']);
	$page->smarty->assign('animeEndDate', $AniDBAPIArray['enddate']);
	$page->smarty->assign('animeDescription', $AniDBAPIArray['description']);
	$page->smarty->assign('animeRating', $AniDBAPIArray['rating']);
	$page->smarty->assign('animeRelated', str_replace('|', ', ', $AniDBAPIArray['related']));
	$page->smarty->assign('animeCategories', str_replace('|', ' - ', $AniDBAPIArray['categories']));

	$page->title = $AniDBAPIArray['title'];
	$page->meta_title = "View Anime " . $AniDBAPIArray['title'];
	$page->meta_keywords = "view,anime,anidb,description,details";
	$page->meta_description = "View " . $AniDBAPIArray['title'] . " Anime";

	$page->content = $page->smarty->fetch('viewanime.tpl');
	$page->render();
} else {
	$letter = (isset($_GET["id"]) && preg_match('/^(0\-9|[A-Z])$/i', $_GET['id'])) ? $_GET['id'] : '0-9';

	$animetitle = (isset($_GET['title']) && !empty($_GET['title'])) ? $_GET['title'] : '';

	if ($animetitle != "" && !isset($_GET["id"])) {
		$letter = "";
	}

	$masterserieslist = $AniDB->getAnimeList($letter, $animetitle);

	$page->title = 'Anime List';
	$page->meta_title = "View Anime List";
	$page->meta_keywords = "view,anime,series,description,details";
	$page->meta_description = "View Anime List";

	$animelist = array();
	foreach ($masterserieslist as $s) {
		if (preg_match('/^[0-9]/', $s['releasetitle'])) {
			$thisrange = '0-9';
		} else {
			preg_match('/([A-Z]).*/i', $s['releasetitle'], $matches);
			$thisrange = strtoupper($matches[1]);
		}
		$animelist[$thisrange][] = $s;
	}
	ksort($animelist);

	$page->smarty->assign('animelist', $animelist);
	$page->smarty->assign('animerange', range('A', 'Z'));
	$page->smarty->assign('animeletter', $letter);
	$page->smarty->assign('animetitle', $animetitle);

	$page->content = $page->smarty->fetch('viewanimelist.tpl');
	$page->render();
}