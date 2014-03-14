<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

$releases = new Releases();
$contents = new Contents();

if (!isset($_REQUEST['t'])) {
	$_REQUEST['t'] = 'Movies';
}

if (!in_array($_REQUEST['t'], array('Books', 'Console', 'Movies', 'MP3', 'Recent'))) {
	$_REQUEST['t'] = 'Movies';
}

$page->smarty->assign('type', $_REQUEST['t']);

switch ($_REQUEST['t']) {
	case 'Movies':
		$getnewestmovies = $releases->getNewestMovies();
		$page->smarty->assign('newest', $getnewestmovies);

		$user = $users->getById($users->currentUserId());
		$page->smarty->assign('cpapi', $user['cp_api']);
		$page->smarty->assign('cpurl', $user['cp_url']);
		break;

	case 'Console':
		$getnewestconsole = $releases->getNewestConsole();
		$page->smarty->assign('newest', $getnewestconsole);
		break;

	case 'MP3':
		$getnewestmp3 = $releases->getnewestMP3s();
		$page->smarty->assign('newest', $getnewestmp3);
		break;

	case 'Books':
		$getnewestbooks = $releases->getNewestBooks();
		$page->smarty->assign('newest', $getnewestbooks);
		break;

	case 'Recent':
		$recent = $releases->getRecentlyAdded();
		$page->smarty->assign('newest', $recent);
		break;

	default:
		$getnewestmovies = $releases->getNewestMovies();
		$page->smarty->assign('newest', $getnewestmovies);
}

$page->content = $page->smarty->fetch('newposterwall.tpl');
$page->render();