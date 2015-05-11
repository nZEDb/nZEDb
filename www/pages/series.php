<?php

use nzedb\Category;
use nzedb\Releases;
use nzedb\TvRage;
use nzedb\UserSeries;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$releases = new Releases(['Settings' => $page->settings]);
$tvrage = new TvRage(['Settings' => $page->settings]);
$cat = new Category(['Settings' => $page->settings]);
$us = new UserSeries(['Settings' => $page->settings]);

if (isset($_GET["id"]) && ctype_digit($_GET['id'])) {
	$category = -1;
	if (isset($_REQUEST["t"]) && ctype_digit($_REQUEST["t"])) {
		$category = $_REQUEST["t"];
	}

	$catarray = array();
	$catarray[] = $category;

	$rel = $releases->searchbyRageId($_GET["id"], '', '', 0, 1000, "", $catarray, -1);
	$rage = $tvrage->getByRageID($_GET['id']);

	if (!$rage) {
		$page->smarty->assign("nodata", "No tvrage information for this series.");
	} elseif (!$rel) {
		$page->smarty->assign("nodata", "No releases for this series.");
	} else {
		$myshows = $us->getShow($page->users->currentUserId(), $rage[0]['rageid']);

		// Sort releases by season, episode, date posted.
		$season = $episode = $posted = array();
		foreach ($rel as $rlk => $rlv) {
			$season[$rlk] = $rlv['season'];
			$episode[$rlk] = $rlv['episode'];
			$posted[$rlk] = $rlv['postdate'];
		}
		array_multisort($season, SORT_DESC, $episode, SORT_DESC, $posted, SORT_DESC, $rel);

		$seasons = array();
		foreach ($rel as $r) {
			$seasons[$r['season']][$r['episode']][] = $r;
		}

		$page->smarty->assign('seasons', $seasons);
		$page->smarty->assign('rage', $rage);
		$page->smarty->assign('myshows', $myshows);

		//get series name(s), description, country and genre
		$seriesnames = $seriesdescription = $seriescountry = $seriesgenre = array();
		foreach ($rage as $r) {
			$seriesnames[] = $r['releasetitle'];
			if (!empty($r['description'])) {
				$seriesdescription[] = $r['description'];
			}

			if (!empty($r['country'])) {
				$seriescountry[] = $r['country'];
			}

			if (!empty($r['genre'])) {
				$seriesgenre[] = str_replace('|', ' - ', $r['genre']);
			}
		}
		$seriesnames = implode('/', array_map("trim", $seriesnames));
		$page->smarty->assign('seriesnames', $seriesnames);
		$page->smarty->assign('seriesdescription', array_shift($seriesdescription));
		$page->smarty->assign('seriescountry', array_shift($seriescountry));
		$page->smarty->assign('seriesgenre', array_shift($seriesgenre));

		$page->title = "$seriesnames";
		$page->meta_title = "View TV Series $seriesnames";
		$page->meta_keywords = "view,series,tv,show,description,details";
		$page->meta_description = "View $seriesnames Series";

		if ($category != -1) {
			$cdata = $cat->getById($category);
			$catid = $category;
		} else {
			$cdata = array('title' => '');
			$catid = '';
		}
		$page->smarty->assign('catname', $cdata["title"]);
		$page->smarty->assign('category', $catid);
	}
	$page->content = $page->smarty->fetch('viewseries.tpl');
	$page->render();
} else {
	$letter = (isset($_GET["id"]) && preg_match('/^(0\-9|[A-Z])$/i', $_GET['id'])) ? $_GET['id'] : '0-9';

	$ragename = (isset($_GET['title']) && !empty($_GET['title'])) ? $_GET['title'] : '';

	if ($ragename != "" && !isset($_GET["id"])) {
		$letter = "";
	}

	$masterserieslist = $tvrage->getSeriesList($page->users->currentUserId(), $letter, $ragename);

	$page->title = 'Series List';
	$page->meta_title = "View Series List";
	$page->meta_keywords = "view,series,tv,show,description,details";
	$page->meta_description = "View Series List";

	$serieslist = array();
	foreach ($masterserieslist as $s) {
		if (preg_match('/^[0-9]/', $s['releasetitle'])) {
			$thisrange = '0-9';
		} else {
			preg_match('/([A-Z]).*/i', $s['releasetitle'], $matches);
			$thisrange = strtoupper($matches[1]);
		}
		$serieslist[$thisrange][] = $s;
	}
	ksort($serieslist);

	$page->smarty->assign('serieslist', $serieslist);
	$page->smarty->assign('seriesrange', range('A', 'Z'));
	$page->smarty->assign('seriesletter', $letter);
	$page->smarty->assign('ragename', $ragename);

	$page->content = $page->smarty->fetch('viewserieslist.tpl');
	$page->render();
}
