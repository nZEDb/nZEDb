<?php
if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$releases = new Releases(['Settings' => $page->settings]);
$contents = new Contents(['Settings' => $page->settings]);
$category = new Category(['Settings' => $page->settings]);
$error = false;

// Array with all the possible poster wall types.
$startTypes = array('Books', 'Console', 'Movies', 'XXX', 'Audio', 'PC', 'TV'/*, 'Recent'*/);
// Array that will contain the poster wall types (the above array minus whatever they have disabled in admin).
$types = array();
// Get the names of all enabled parent categories.
$categories = $category->getEnabledParentNames();
// Loop through our possible ones and check if they are in the enabled categories.
if (count($categories) > 0) {
	foreach ($categories as $pType) {
		if (in_array($pType['title'], $startTypes)) {
			$types[] = $pType['title'];
		}
	}
} else {
	$error = "No categories are enabled!";
}

if (count($types) === 0) {
	$error = 'No categories enabled for the new poster wall. Possible choices are: ' . implode(', ', $startTypes) . '.';
}

if (!$error) {

	// Check if the user did not pass the required t parameter, set it to the first type.
	if (!isset($_REQUEST['t'])) {
		$_REQUEST['t'] = $types[0];
	}

	// Check if the user passed an invalid t parameter.
	if (!in_array($_REQUEST['t'], $types)) {
		$_REQUEST['t'] = $types[0];
	}

	$page->smarty->assign('types', $types);
	$page->smarty->assign('type', $_REQUEST['t']);

	switch ($_REQUEST['t']) {
		case 'Movies':
			$getnewestmovies = $releases->getNewestMovies();
			$page->smarty->assign('newest', $getnewestmovies);

			$user = $page->users->getById($page->users->currentUserId());
			$page->smarty->assign('cpapi', $user['cp_api']);
			$page->smarty->assign('cpurl', $user['cp_url']);
			$page->smarty->assign('goto', 'movies');
			break;

		case 'Console':
			$getnewestconsole = $releases->getNewestConsole();
			$page->smarty->assign('newest', $getnewestconsole);
			$page->smarty->assign('goto', 'console');
			break;

		case 'XXX':
			$getnewestxxx = $releases->getNewestXXX();
			$page->smarty->assign('newest', $getnewestxxx);
			$page->smarty->assign('goto', 'xxx');
			break;

        case 'PC':
            $getnewestgame = $releases->getNewestGames();
            $page->smarty->assign('newest', $getnewestgame);
            $page->smarty->assign('goto', 'games');
            break;

		case 'Audio':
			$getnewestmp3 = $releases->getnewestMP3s();
			$page->smarty->assign('newest', $getnewestmp3);
			$page->smarty->assign('goto', 'music');
			break;

		case 'Books':
			$getnewestbooks = $releases->getNewestBooks();
			$page->smarty->assign('newest', $getnewestbooks);
			$page->smarty->assign('goto', 'books');
			break;

		case 'TV':
			$getnewesttv = $releases->getNewestTV();
			$page->smarty->assign('newest', $getnewesttv);
			$page->smarty->assign('goto', 'tv');
			break;

		case 'Recent':
			$recent = $releases->getRecentlyAdded();
			$page->smarty->assign('newest', $recent);
			$page->smarty->assign('goto', 'browse');
			break;

		default:
			$error = "ERROR: Invalid ?t parameter (" . $_REQUEST['t'] . ").";
	}
}
$page->title = 'New ' . $_REQUEST['t'] . ' Releases';
$page->meta_title = $_REQUEST['t'] . ' Poster Wall';
$page->meta_keywords = "view,new,releases,posters,wall";
$page->meta_description = "The newest " . $_REQUEST['t'] . ' releases';
$page->smarty->assign('error', $error);
$page->content = $page->smarty->fetch('newposterwall.tpl');
$page->render();