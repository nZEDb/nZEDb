<?php
if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$Releases = new Releases(['Settings' => $page->settings]);
$AniDB = new AniDB(['Settings' => $page->settings]);

if (isset($_GET['id']) && ctype_digit($_GET['id'])) {

	# force the category to 5070 as it should be for anime, as $catarray was NULL and we know the category for sure for anime
	$releases = $Releases->searchbyAnidbId($_GET['id'], '', 0, 1000, '', array('5070'), -1);
	$anidb = $AniDB->getAnimeInfo($_GET['id']);

	if (!$releases && !$anidb) {
		$page->show404();
	} else if (!$anidb) {
		$page->smarty->assign('nodata', 'No AniDB information for this series.');
	} elseif (!$releases) {
		$page->smarty->assign('nodata', 'No releases for this series.');
	} else {
		// Sort releases by season, episode, date posted.
		$season = $episode = $posted = array();

		foreach ($releases as $rlk => $rlv) {
			$season[$rlk] = $rlv['season'];
			$episode[$rlk] = $rlv['episode'];
			$posted[$rlk] = $rlv['postdate'];
		}

		array_multisort($episode, SORT_DESC, $posted, SORT_DESC, $releases);

		$seasons = array();
		foreach ($releases as $r) {
			$seasons[$r['season']][$r['episode']][] = $r;
		}

		$page->smarty->assign('seasons', $seasons);
		$page->smarty->assign('anidb', $anidb);

		$animeEpisodeTitles = array();
		foreach ($releases as $r) {
			$animeEpisodeTitles[$r['tvtitle']][] = $r;
		}

		$page->smarty->assign('animeEpisodeTitlesSize', count($animeEpisodeTitles));
		$page->smarty->assign('animeEpisodeTitles', $animeEpisodeTitles);
		$page->smarty->assign('animeAnidbID', $anidb['anidbid']);
		# case is off on old variable this resolves that, I do not think the other is ever used, but left if anyways
		$page->smarty->assign('animeAnidbid', $anidb['anidbid']);
		$page->smarty->assign('animeTitle', $anidb['title']);
		$page->smarty->assign('animeType', $anidb['type']);
		$page->smarty->assign('animePicture', $anidb['picture']);
		$page->smarty->assign('animeStartDate', $anidb['startdate']);
		$page->smarty->assign('animeEndDate', $anidb['enddate']);
		$page->smarty->assign('animeDescription', $anidb['description']);
		$page->smarty->assign('animeRating', $anidb['rating']);
		$page->smarty->assign('animeRelated', $anidb['related']);
		$page->smarty->assign('animeSimilar', $anidb['similar']);
		$page->smarty->assign('animeCategories', $anidb['categories']);

		$page->title = $anidb['title'];
		$page->meta_title = 'View Anime ' . $anidb['title'];
		$page->meta_keywords = 'view,anime,anidb,description,details';
		$page->meta_description = 'View ' . $anidb['title'] . ' Anime';
	}
	$page->content = $page->smarty->fetch('viewanime.tpl');
	$page->render();
} else {
	$letter = (isset($_GET['id']) && preg_match('/^(0\-9|[A-Z])$/i', $_GET['id'])) ? $_GET['id'] : '0-9';

	$animetitle = (isset($_GET['title']) && !empty($_GET['title'])) ? $_GET['title'] : '';

	if ($animetitle != '' && !isset($_GET['id'])) {
		$letter = '';
	}

	$masterserieslist = $AniDB->getAnimeList($letter, $animetitle);

	$page->title = 'Anime List';
	$page->meta_title = 'View Anime List';
	$page->meta_keywords = 'view,anime,series,description,details';
	$page->meta_description = 'View Anime List';

	$animelist = array();
	if ($masterserieslist instanceof \Traversable) {
		foreach ($masterserieslist as $s) {
			if (preg_match('/^[0-9]/', $s['title'])) {
				$thisrange = '0-9';
			} else {
				preg_match('/([A-Z]).*/i', $s['title'], $matches);
				$thisrange = strtoupper($matches[1]);
			}
			$animelist[$thisrange][] = $s;
		}
		ksort($animelist);
	}

	$page->smarty->assign('animelist', $animelist);
	$page->smarty->assign('animerange', range('A', 'Z'));
	$page->smarty->assign('animeletter', $letter);
	$page->smarty->assign('animetitle', $animetitle);

	$page->content = $page->smarty->fetch('viewanimelist.tpl');
	$page->render();
}
