<?php

use nzedb\db\Settings;

if (is_file("config.php")) {
	require_once './config.php';
} else {
	if (is_dir("install")) {
		header("location: install");
		exit();
	}
}
require_once 'automated.config.php';

$page = new Page();

switch ($page->page) {
	case 'ajax_mediainfo':
	case 'ajax_mymovies':
	case 'ajax_preinfo':
	case 'ajax_profile':
	case 'ajax_release-admin':
	case 'ajax_rarfilelist':
	case 'ajax_titleinfo':
	case 'ajax_tvinfo':
	case 'anime':
	case 'apihelp':
	case 'bookmodal':
	case 'books':
	case 'browse':
	case 'browsegroup':
	case 'calendar':
	case 'cart':
	case 'console':
	case 'consolemodal':
	case 'contact-us':
	case 'content':
	case 'details':
	case 'filelist':
	case 'forgottenpassword':
	case 'forum':
	case 'forumpost':
	case 'games':
	case 'getimage':
	case 'logout':
	case 'movies':
	case 'movie':
	case 'music':
	case 'musicmodal':
	case 'myshows':
	case 'mymovies':
	case 'mymoviesedit':
	case 'newposterwall':
	case 'nfo':
	case 'nzbgetqueuedata':
	case 'predb':
	case 'profile':
	case 'profileedit':
	case 'queue':
	case 'register':
	case 'rss-info':
	case 'sabqueuedata':
	case 'search':
	case 'sendtoqueue':
	case 'series':
	case 'sitemap':
	case 'sysinfo':
	case 'terms-and-conditions':
	case 'upcoming':
	case 'xxx':
	case 'xxxmodal':
		// Don't show these pages if it's an API-only site.
		if (!$page->users->isLoggedIn() && $page->settings->getSetting('registerstatus') == Settings::REGISTER_STATUS_API_ONLY) {
			header("Location: " . $page->settings->getSetting('code'));
			break;
		}
	case 'api':
	case 'getnzb':
	case 'login':
	case 'preinfo':
	case 'rss':
		include(nZEDb_WWW . 'pages/' . $page->page . '.php');
		break;
	default:
		$page->show404();
		break;
}
