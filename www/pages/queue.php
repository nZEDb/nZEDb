<?php
if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$userData = $page->users->getById($page->users->currentUserId());
if (!$userData) {
	$page->show404();
}
$page->smarty->assign('user', $userData);

$queueType = $error = '';
$queue     = null;
switch ($page->settings->getSetting('sabintegrationtype')) {
	case SABnzbd::INTEGRATION_TYPE_NONE:
		if ($userData['queuetype'] == 2) {
			$queueType = 'NZBGet';
			$queue     = new NZBGet($page);
		}
		break;
	case SABnzbd::INTEGRATION_TYPE_SITEWIDE:
		$queueType = 'Sabnzbd';
		$queue     = new SABnzbd($page);
		break;
	case SABnzbd::INTEGRATION_TYPE_USER:
		switch ((int)$userData['queuetype']) {
			case 1:
				$queueType = 'Sabnzbd';
				$queue     = new SABnzbd($page);
				break;
			case 2:
				$queueType = 'NZBGet';
				$queue     = new NZBGet($page);
				break;
		}
		break;
}

if (!is_null($queue)) {

	if ($queueType === 'Sabnzbd') {
		if (empty($queue->url)) {
			$error = 'ERROR: The Sabnzbd URL is missing!';
		}

		if (empty($queue->apikey)) {
			if ($error === '') {
				$error = 'ERROR: The Sabnzbd API key is missing!';
			} else {
				$error .= ' The Sabnzbd API key is missing!';
			}
		}
	}

	if ($error === '') {
		if (isset($_REQUEST["del"])) {
			$queue->delFromQueue($_REQUEST['del']);
		}

		if (isset($_REQUEST["pause"])) {
			$queue->pauseFromQueue($_REQUEST['pause']);
		}

		if (isset($_REQUEST["resume"])) {
			$queue->resumeFromQueue($_REQUEST['resume']);
		}

		if (isset($_REQUEST["pall"])) {
			$queue->pauseAll();
		}

		if (isset($_REQUEST["rall"])) {
			$queue->resumeAll();
		}

		$page->smarty->assign('serverURL', $queue->url);
	}
}

$page->smarty->assign(['queueType' => $queueType, 'error' => $error, 'user', $userData]);
$page->title            = "Your $queueType Download Queue";
$page->meta_title       = "View $queueType Queue";
$page->meta_keywords    = "view," . strtolower($queueType) . ",queue";
$page->meta_description = "View $queueType Queue";

$page->content = $page->smarty->fetch('viewqueue.tpl');
$page->render();
