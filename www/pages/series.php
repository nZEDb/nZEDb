<?php

use nzedb\Category;
use nzedb\Releases;
use nzedb\Videos;
use nzedb\UserSeries;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$releases = new Releases(['Settings' => $page->settings]);
$tvshow = new Videos(['Settings' => $page->settings]);
$cat = new Category(['Settings' => $page->settings]);
$us = new UserSeries(['Settings' => $page->settings]);

if (isset($_GET["id"]) && ctype_digit($_GET['id'])) {
	$category = -1;
	if (isset($_REQUEST["t"]) && ctype_digit($_REQUEST["t"])) {
		$category = $_REQUEST["t"];
	}

	$catarray = array();
	$catarray[] = $category;

	$rel = $releases->searchShows(['id' => $_GET["id"]], '', '', '', 0, 1000, '', $catarray, -1);
	$show = $tvshow->getByVideoID($_GET['id']);

	if (!$show) {
		$page->smarty->assign("nodata", "No video information for this series.");
	} elseif (!$rel) {
		$page->smarty->assign("nodata", "No releases for this series.");
	} else {
		$myshows = $us->getShow($page->users->currentUserId(), $show['id']);

		// Sort releases by season, episode, date posted.
		$series = $episode = $posted = array();
		foreach ($rel as $rlk => $rlv) {
			$series[$rlk] = $rlv['series'];
			$episode[$rlk] = $rlv['episode'];
			$posted[$rlk] = $rlv['postdate'];
		}
		array_multisort($series, SORT_DESC, $episode, SORT_DESC, $posted, SORT_DESC, $rel);

		$series = array();
		foreach ($rel as $r) {
			$series[$r['series']][$r['episode']][] = $r;
		}

		$page->smarty->assign('seasons', $series);
		$page->smarty->assign('show', $show);
		$page->smarty->assign('myshows', $myshows);

		//get series name(s), description, country and genre
		$seriestitles = $seriesdescription = $seriescountry = array();
		$seriestitles[] = $show['title'];

		if (!empty($show['summary'])) {
			$seriessummary[] = $show['summary'];
		}

		if (!empty($show['country_id'])) {
			$seriescountry[] = $show['country_id'];
		}

		$seriestitles = implode('/', array_map("trim", $seriestitles));
		$page->smarty->assign('seriestitles', $seriestitles);
		$page->smarty->assign('seriessummary', array_shift($seriessummary));
		$page->smarty->assign('seriescountry', array_shift($seriescountry));

		$page->title = "Series";
		$page->meta_title = "View TV Series";
		$page->meta_keywords = "view,series,tv,show,description,details";
		$page->meta_description = "View TV Series";

		if ($category != -1) {
			$cdata = $cat->getById($category);
			$catid = $category;
		} else {
			$cdata = array('title' => '');
			$catid = '';
		}
		$page->smarty->assign('catname', $cdata['title']);
		$page->smarty->assign('category', $catid);
		$page->smarty->assign("nodata", '');
	}
	$page->content = $page->smarty->fetch('viewseries.tpl');
	$page->render();
} else {
	$letter = (isset($_GET["id"]) && preg_match('/^(0\-9|[A-Z])$/i', $_GET['id'])) ? $_GET['id'] : '0-9';

	$showname = (isset($_GET['title']) && !empty($_GET['title'])) ? $_GET['title'] : '';

	if ($showname != "" && !isset($_GET["id"])) {
		$letter = "";
	}

	$masterserieslist = $tvshow->getSeriesList($page->users->currentUserId(), $letter, $showname);

	$page->title = 'Series List';
	$page->meta_title = "View Series List";
	$page->meta_keywords = "view,series,tv,show,description,details";
	$page->meta_description = "View Series List";

	$serieslist = array();
	foreach ($masterserieslist as $s) {
		if (preg_match('/^[0-9]/', $s['title'])) {
			$thisrange = '0-9';
		} else {
			preg_match('/([A-Z]).*/i', $s['title'], $matches);
			$thisrange = strtoupper($matches[1]);
		}
		$serieslist[$thisrange][] = $s;
	}
	ksort($serieslist);

	$page->smarty->assign('serieslist', $serieslist);
	$page->smarty->assign('seriesrange', range('A', 'Z'));
	$page->smarty->assign('seriesletter', $letter);
	$page->smarty->assign('showname', $showname);

	$page->content = $page->smarty->fetch('viewserieslist.tpl');
	$page->render();
}
