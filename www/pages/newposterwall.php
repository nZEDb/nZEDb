<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

$releases = new Releases();
$contents = new Contents();

if (!isset($_REQUEST['t'])) {
	$_REQUEST['t'] = 'Movies';
}

$types = array(/*'Books', 'Console', */'Movies', 'Music'/*, 'Recent'*/);

if (!in_array($_REQUEST['t'], $types)) {
	$_REQUEST['t'] = 'Movies';
}

$page->smarty->assign('types', $types);
$page->smarty->assign('type', $_REQUEST['t']);

switch ($_REQUEST['t']) {
	case 'Movies':
		$getnewestmovies = $releases->getNewestMovies();
		$page->smarty->assign('newest', $getnewestmovies);

		$user = $users->getById($users->currentUserId());
		$page->smarty->assign('cpapi', $user['cp_api']);
		$page->smarty->assign('cpurl', $user['cp_url']);
		$page->smarty->assign('goto', 'movies');
		break;

	case 'Console':
		$getnewestconsole = $releases->getNewestConsole();
		$page->smarty->assign('newest', $getnewestconsole);
		$page->smarty->assign('goto', 'console');
		break;

	case 'Music':
		$getnewestmp3 = $releases->getnewestMP3s();
		$page->smarty->assign('newest', $getnewestmp3);
		$page->smarty->assign('goto', 'music');
		break;

	case 'Books':
		$getnewestbooks = $releases->getNewestBooks();
		$page->smarty->assign('newest', $getnewestbooks);
		$page->smarty->assign('goto', 'browse?t=8000');
		break;

	case 'Recent':
		$recent = $releases->getRecentlyAdded();
		$page->smarty->assign('newest', $recent);
		$page->smarty->assign('goto', 'browse');
		break;

	default:
		$getnewestmovies = $releases->getNewestMovies();
		$page->smarty->assign('newest', $getnewestmovies);

		$user = $users->getById($users->currentUserId());
		$page->smarty->assign('cpapi', $user['cp_api']);
		$page->smarty->assign('cpurl', $user['cp_url']);
		$page->smarty->assign('goto', 'movies');
}

$page->content = $page->smarty->fetch('newposterwall.tpl');
$page->render();