<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

$queueType = $error = '';

if ($page->site->sabintegrationtype > 0) {

	$queueType = 'Sabnzbd';

	$sab = new SABnzbd($page);

	if (empty($sab->url)) {
		$error = 'ERROR: The Sabnzbd URL is missing!';
	}

	if (empty($sab->apikey)) {
		if ($error === '') {
			$error = 'ERROR: The Sabnzbd API key is missing!';
		} else {
			$error .= ' The Sabnzbd API key is missing!';
		}
	}

	if ($error === '') {
		if (isset($_REQUEST["del"])) {
			$sab->delFromQueue($_REQUEST['del']);
		}

		if (isset($_REQUEST["pause"])) {
			$sab->pauseFromQueue($_REQUEST['pause']);
		}

		if (isset($_REQUEST["resume"])) {
			$sab->resumeFromQueue($_REQUEST['resume']);
		}

		if (isset($_REQUEST["pall"])) {
			$sab->pauseAll($_REQUEST['pall']);
		}

		if (isset($_REQUEST["rall"])) {
			$sab->resumeAll($_REQUEST['rall']);
		}

		$page->smarty->assign('sabserver', $sab->url);
	}
}

$page->smarty->assign(array('queueType' => $queueType, 'error' => $error));
$page->title = "Your $queueType Download Queue";
$page->meta_title = "View $queueType Queue";
$page->meta_keywords = "view," . strtolower($queueType) .",queue";
$page->meta_description = "View $queueType Queue";

$page->content = $page->smarty->fetch('viewqueue.tpl');
$page->render();
