<?php

use nzedb\db\DB;

// API functions.
$function = 's';
if (isset($_GET['t'])) {
	if ($_GET['t'] == 'details' || $_GET['t'] == 'd') {
		$function = 'd';
	} elseif ($_GET['t'] == 'get' || $_GET['t'] == 'g') {
		$function = 'g';
	} elseif ($_GET['t'] == 'search' || $_GET['t'] == 's') {
		$function = 's';
	} elseif ($_GET['t'] == 'caps' || $_GET['t'] == 'c') {
		$function = 'c';
	} elseif ($_GET['t'] == 'tvsearch' || $_GET['t'] == 'tv') {
		$function = 'tv';
	} elseif ($_GET['t'] == 'movie' || $_GET['t'] == 'm') {
		$function = 'm';
	} elseif ($_GET['t'] == 'register' || $_GET['t'] == 'r') {
		$function = 'r';
	} else {
		showApiError(202);
	}
} else {
	showApiError(200);
}

function getAudioReleases($rID)
{
	$db = new DB();
	return $db->query('SELECT * FROM releaseaudio WHERE releaseid = ' . $rID);
}

$releases = new Releases();
$category = new Category();
$nzb = new NZB();

$user = $uid = $apikey = '';
$catexclusions = array();
// Page is accessible only by the apikey, or logged in users.
if ($users->isLoggedIn()) {
	$uid = $page->userdata['id'];
	$apikey = $page->userdata['rsstoken'];
	$catexclusions = $page->userdata['categoryexclusions'];
	$maxrequests = $page->userdata['apirequests'];
} else {
	if ($function != 'c' && $function != 'r') {
		if ($page->site->registerstatus == Sites::REGISTER_STATUS_API_ONLY) {
			$res = $users->getById(0);
			$apikey = '';
		} else {
			if (!isset($_GET['apikey'])) {
				showApiError(100);
			}
			$res = $users->getByRssToken($_GET['apikey']);
			$apikey = $_GET['apikey'];
		}

		if (!$res) {
			showApiError(100);
		}

		$uid = $res['id'];
		$catexclusions = $users->getCategoryExclusion($uid);
		$maxrequests = $res['apirequests'];
	}
}

// Record user access to the api, if its been called by a user (i.e. capabilities request do not require a user to be logged in or key provided).
if ($uid != '') {
	$users->updateApiAccessed($uid);
	$apirequests = $users->getApiRequests($uid);
	if ($apirequests['num'] > $maxrequests) {
		showApiError(500);
	}
}

$page->smarty->assign('uid', $uid);
$page->smarty->assign('rsstoken', $apikey);
if (isset($_GET['extended']) && $_GET['extended'] == '1') {
	$page->smarty->assign('extended', '1');
}
if (isset($_GET['del']) && $_GET['del'] == '1') {
	$page->smarty->assign('del', '1');
}

// Output is either json or xml.
$outputtype = 'xml';
if (isset($_GET['o'])) {
	if ($_GET['o'] == 'json') {
		$outputtype = 'json';
	}
}

switch ($function) {
	// Search releases.
	case 's':
		if (isset($_GET['q']) && $_GET['q'] == '') {
			showApiError(200);
		}

		$maxage = -1;
		if (isset($_GET['maxage'])) {
			if ($_GET['maxage'] == '') {
				showApiError(200);
			} elseif (!is_numeric($_GET['maxage'])) {
				showApiError(201);
			} else {
				$maxage = $_GET['maxage'];
			}
		}

		$users->addApiRequest($uid, $_SERVER['REQUEST_URI']);

		$categoryId = array();
		if (isset($_GET['cat'])) {
			$categoryId = explode(',', $_GET['cat']);
		} else {
			$categoryId[] = -1;
		}

		$limit = 100;
		if (isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] < 100) {
			$limit = $_GET['limit'];
		}

		$offset = 0;
		if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
			$offset = $_GET['offset'];
		}

		if (isset($_GET['q'])) {
			$reldata = $releases->search($_GET['q'], -1, -1, -1, $categoryId, -1, -1, 0, 0, -1, -1, $offset, $limit, '', $maxage, $catexclusions);
		} else {
			$orderby = array();
			$orderby[0] = 'postdate';
			$orderby[1] = 'asc';
			$totrows = $releases->getBrowseCount($categoryId, $maxage, $catexclusions);
			$reldata = $releases->getBrowseRange($categoryId, $offset, $limit, '', $maxage, $catexclusions);
			if ($totrows > 0 && count($reldata)) {
				$reldata[0]['_totalrows'] = $totrows;
			}
		}

		if ($outputtype == 'xml') {
			$page->smarty->assign('offset', $offset);
			$page->smarty->assign('releases', $reldata);
			header('Content-type: text/xml');
			echo trim($page->smarty->fetch('apiresult.tpl'));
		}
		// TODO: Make that a more specific array of data to return rather than resultset.
		else {
			echo json_encode($reldata);
		}
		break;

	// Search tv releases.
	case 'tv':
		if (isset($_GET['q']) && $_GET['q'] == '') {
			showApiError(200);
		}

		$categoryId = array();
		if (isset($_GET['cat'])) {
			$categoryId = explode(',', $_GET['cat']);
		} else {
			$categoryId[] = -1;
		}

		$maxage = -1;
		if (isset($_GET['maxage'])) {
			if ($_GET['maxage'] == '') {
				showApiError(200);
			} elseif (!is_numeric($_GET['maxage'])) {
				showApiError(201);
			} else {
				$maxage = $_GET['maxage'];
			}
		}

		if (isset($_GET['rid']) && $_GET['rid'] == '') {
			showApiError(200);
		}
		if (isset($_GET['season']) && $_GET['season'] == '') {
			showApiError(200);
		}
		if (isset($_GET['ep']) && $_GET['ep'] == '') {
			showApiError(200);
		}

		$users->addApiRequest($uid, $_SERVER['REQUEST_URI']);

		$limit = 100;
		if (isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] < 100) {
			$limit = $_GET['limit'];
		}

		$offset = 0;
		if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
			$offset = $_GET['offset'];
		}

		$reldata = $releases->searchbyRageId((isset($_GET['rid']) ? $_GET['rid'] : '-1'), (isset($_GET['season']) ? $_GET['season'] : ''), (isset($_GET['ep']) ? $_GET['ep'] : ''), $offset, $limit, (isset($_GET['q']) ? $_GET['q'] : ''), $categoryId, $maxage);

		$langdata = array();
		foreach ($reldata as $release) {
			$audios = getAudioReleases($release['id']);
			foreach ($audios as $audio) {
				if ($audio['audiolanguage'] == '') {
					$release['searchname'] = $release['searchname'];
				} else {
					$release['searchname'] = $release['searchname'] . ' ' . $audio['audiolanguage'];
				}
			}
			$langdata[] = $release;
		}

		if ($outputtype == 'xml') {
			$page->smarty->assign('offset', $offset);
			$page->smarty->assign('releases', $langdata);
			header('Content-type: text/xml');
			echo trim($page->smarty->fetch('apiresult.tpl'));
		}
		// TODO: Make that a more specific array of data to return rather than resultset.
		else {
			echo json_encode($langdata);
		}
		break;

	// Search movie releases.
	case 'm':
		if (isset($_GET['q']) && $_GET['q'] == '') {
			showApiError(200);
		}

		$categoryId = array();
		if (isset($_GET['cat'])) {
			$categoryId = explode(',', $_GET['cat']);
		} else {
			$categoryId[] = -1;
		}

		$maxage = -1;
		if (isset($_GET['maxage'])) {
			if ($_GET['maxage'] == '') {
				showApiError(200);
			} elseif (!is_numeric($_GET['maxage'])) {
				showApiError(201);
			} else {
				$maxage = $_GET['maxage'];
			}
		}
		if (isset($_GET['imdbid']) && $_GET['imdbid'] == '') {
			showApiError(200);
		}

		$users->addApiRequest($uid, $_SERVER['REQUEST_URI']);

		$limit = 100;
		if (isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] < 100) {
			$limit = $_GET['limit'];
		}

		$offset = 0;
		if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
			$offset = $_GET['offset'];
		}
		$reldata = $releases->searchbyImdbId((isset($_GET['imdbid']) ? $_GET['imdbid'] : '-1'), $offset, $limit, (isset($_GET['q']) ? $_GET['q'] : ''), $categoryId, $maxage);

		$langdata = array();
		foreach ($reldata as $release) {
			$audios = getAudioReleases($release['id']);
			foreach ($audios as $audio) {
				if ($audio['audiolanguage'] == '') {
					$release['searchname'] = $release['searchname'];
				} else {
					$release['searchname'] = $release['searchname'] . ' ' . $audio['audiolanguage'];
				}
			}
			$langdata[] = $release;
		}

		if ($outputtype == 'xml') {
			$page->smarty->assign('offset', $offset);
			$page->smarty->assign('releases', $langdata);
			header('Content-type: text/xml');
			echo trim($page->smarty->fetch('apiresult.tpl'));
		}
		// TODO: Make that a more specific array of data to return rather than resultset.
		else {
			echo json_encode($langdata);
		}
		break;

	// Get NZB.
	case 'g':
		if (!isset($_GET['id'])) {
			showApiError(200);
		}

		$del = '';
		if (isset($_GET['del']) && $_GET['del'] == '1') {
			$del = '&del=1';
		}

		$reldata = $releases->getByGuid($_GET['id']);
		if ($reldata) {
			header('Location:' . WWW_TOP . '/getnzb?i=' . $uid . '&r=' . $apikey . '&id=' . $reldata['guid'] . $del);
		} else {
			showApiError(300);
		}
		break;

	// Get individual nzb details.
	case 'd':
		if (!isset($_GET['id'])) {
			showApiError(200);
		}

		$users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$data = $releases->getByGuid($_GET['id']);

		if ($data) {
			$reldata[] = $data;
		} else {
			$reldata = array();
		}

		if ($outputtype == 'xml') {
			$page->smarty->assign('releases', $reldata);
			header('Content-type: text/xml');
			echo trim($page->smarty->fetch('apidetail.tpl'));
		}
		// TODO: Make that a more specific array of data to return rather than resultset.
		else {
			echo json_encode($data);
		}

		break;

	// Capabilities request.
	case 'c':
		$parentcatlist = $category->getForMenu();
		$page->smarty->assign('parentcatlist', $parentcatlist);
		header('Content-type: text/xml');
		echo $page->smarty->fetch('apicaps.tpl');
		break;

	// Register request.
	case 'r':
		if (!isset($_GET['email']) || $_GET['email'] == '') {
			showApiError(200);
		}

		if ($page->site->registerstatus != Sites::REGISTER_STATUS_OPEN) {
			showApiError(104);
		}

		// Check email is valid format.
		if (!$users->isValidEmail($_GET['email'])) {
			showApiError(106);
		}

		// Check email isnt taken.
		$ret = $users->getByEmail($_GET['email']);
		if (isset($ret['id'])) {
			showApiError(105);
		}

		// Create uname/pass and register.
		$username = $users->generateUsername($_GET['email']);
		$password = $users->generatePassword();

		// Register.
		$userdefault = $users->getDefaultRole();
		$uid = $users->signup($username, $password, $_GET['email'], $_SERVER['REMOTE_ADDR'], $userdefault['id'], $userdefault['defaultinvites']);
		$userdata = $users->getById($uid);
		if (!$userdata) {
			showApiError(107);
		}

		header('Content-type: text/xml');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . '<register username="' . $username . '" password="' . $password . '" apikey="' . $userdata['rsstoken'] . "\"/>\n";
		break;

	default:
		showApiError(202);
		break;
}

function showApiError($errcode = 900, $errtext = '')
{
	switch ($errcode) {
		case 100:
			$errtext = 'Incorrect user credentials';
			break;
		case 101:
			$errtext = 'Account suspended';
			break;
		case 102:
			$errtext = 'Insufficient priviledges/not authorized';
			break;
		case 103:
			$errtext = 'Registration denied';
			break;
		case 104:
			$errtext = 'Registrations are closed';
			break;
		case 105:
			$errtext = 'Invalid registration (Email Address Taken)';
			break;
		case 106:
			$errtext = 'Invalid registration (Email Address Bad Format)';
			break;
		case 107:
			$errtext = 'Registration Failed (Data error)';
			break;
		case 200:
			$errtext = 'Missing parameter';
			break;
		case 201:
			$errtext = 'Incorrect parameter';
			break;
		case 202:
			$errtext = 'No such function';
			break;
		case 203:
			$errtext = 'Function not available';
			break;
		case 300:
			$errtext = 'No such item';
			break;
		case 500:
			$errtext = 'Request limit reached';
			break;
		case 501:
			$errtext = 'Download limit reached';
			break;
		default:
			$errtext = 'Unknown error';
			break;
	}

	header('Content-type: text/xml');
	exit("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<error code=\"$errcode\" description=\"$errtext\"/>\n");
}
