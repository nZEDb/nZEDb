<?php

use app\models\Settings;
use nzedb\Releases;
use nzedb\http\API;
use nzedb\db\DB;
use nzedb\utility\Misc;
use nzedb\utility\Text;

// API functions.
$function = 's';
if (isset($_GET['t'])) {
	switch ($_GET['t']) {
		case 'd':
		case 'details':
			$function = 'd';
			break;
		case 'g':
		case 'get':
			$function = 'g';
			break;
		case 's':
		case 'search':
			$function = 's';
			break;
		case 'c':
		case 'caps':
			$function = 'c';
			break;
		case 'tv':
		case 'tvsearch':
			$function = 'tv';
			break;
		case 'm':
		case 'movie':
			$function = 'm';
			break;
		case 'gn':
		case 'n':
		case 'nfo':
		case 'info':
			$function = 'n';
			break;
		case 'r':
		case 'register':
			$function = 'r';
			break;
		default:
			Misc::showApiError(202, 'No such function (' . $_GET['t'] . ')');
	}
} else {
	Misc::showApiError(200, 'Missing parameter (t)');
}

$uid = $apiKey = '';
$result = $catExclusions = [];
$maxRequests = 0;

// Page is accessible only by the apikey.
if ($function != 'c' && $function != 'r') {
	if (!isset($_GET['apikey'])) {
		Misc::showApiError(403, 'Missing parameter (apikey)');
	} else {
		$apiKey = $_GET['apikey'];
		$result = $page->users->getByRssToken($apiKey);
		if ($result === false) {
			Misc::showApiError(403, 'Incorrect user credentials (wrong API key)');
		}
	}

	if ($page->users->isDisabled($result['username'])) {
		Misc::showApiError(403, 'Account suspended');
	}

	$uid = $result['id'];
	$catExclusions = $page->users->getCategoryExclusion($uid);
	$maxRequests = $result['apirequests'];
}

// Record user access to the api, if its been called by a user (i.e. capabilities request do not require a user to be logged in or key provided).
if ($uid != '') {
	$page->users->updateApiAccessed($uid);
	$apiRequests = $page->users->getApiRequests($uid);
	if ($apiRequests > $maxRequests) {
		Misc::showApiError(429, 'Request limit reached (' . $apiRequests . '/' . $maxRequests .
			')');
	}
}

$releases = new Releases(['Settings' => $page->settings]);
$api = new API(['Settings' => $page->settings, 'Request' => $_GET]);

// Set Query Parameters based on Request objects
$outputXML = (isset($_GET['o']) && $_GET['o'] == 'json' ? false : true);
$minSize = (isset($_GET['minsize']) && $_GET['minsize'] > 0 ? $_GET['minsize'] : 0);
$offset = $api->offset();

// Set API Parameters based on Request objects
$params['extended'] = (isset($_GET['extended']) && $_GET['extended'] == 1 ? '1' : '0');
$params['del'] = (isset($_GET['del']) && $_GET['del'] == 1 ? '1' : '0');
$params['uid'] = $uid;
$params['token'] = $apiKey;

switch ($function) {
	// Search releases.
	case 's':
		$api->verifyEmptyParameter('q');
		$maxAge = $api->maxAge();
		$groupName = $api->group();
		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$categoryID = $api->categoryID();
		$limit = $api->limit();

		if (isset($_GET['q'])) {
			$relData = $releases->search(
				$_GET['q'], -1, -1, -1, $groupName, -1, -1, 0, 0, -1, -1, $offset, $limit, '', $maxAge, $catExclusions,
				"basic", $categoryID, $minSize
			);
		} else {
			$relData = $releases->getBrowseRange(
				$categoryID, $offset, $limit, '', $maxAge, $catExclusions, $groupName, $minSize
			);
		}
		$api->output($relData, $params, $outputXML, $offset, 'api');
		break;
	// Search tv releases.
	case 'tv':
		$api->verifyEmptyParameter('q');
		$api->verifyEmptyParameter('vid');
		$api->verifyEmptyParameter('tvdbid');
		$api->verifyEmptyParameter('traktid');
		$api->verifyEmptyParameter('rid');
		$api->verifyEmptyParameter('tvmazeid');
		$api->verifyEmptyParameter('imdbid');
		$api->verifyEmptyParameter('tmdbid');
		$api->verifyEmptyParameter('season');
		$api->verifyEmptyParameter('ep');
		$maxAge = $api->maxAge();
		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);

		$siteIdArr = [
			'id'     => (isset($_GET['vid']) ? $_GET['vid'] : '0'),
			'tvdb'   => (isset($_GET['tvdbid']) ? $_GET['tvdbid'] : '0'),
			'trakt'  => (isset($_GET['traktid']) ? $_GET['traktid'] : '0'),
			'tvrage' => (isset($_GET['rid']) ? $_GET['rid'] : '0'),
			'tvmaze' => (isset($_GET['tvmazeid']) ? $_GET['tvmazeid'] : '0'),
			'imdb'   => (isset($_GET['imdbid']) ? $_GET['imdbid'] : '0'),
			'tmdb'   => (isset($_GET['tmdbid']) ? $_GET['tmdbid'] : '0')
		];

		// Process season only queries or Season and Episode/Airdate queries
		$airdate = $episode = $series = '';
		if (!empty($_GET['season']) && !empty($_GET['ep'])) {
			if (preg_match('#^(19|20)\d{2}$#', $_GET['season'], $year) && stripos($_GET['ep'], '/') !== false) {
				$airdate = str_replace('/', '-', $year[0] . '-' . $_GET['ep']);
			} else {
				$series = $_GET['season'];
				$episode = $_GET['ep'];
			}
		} elseif (!empty($_GET['season'])) {
			$series = $_GET['season'];
			$episode = (!empty($_GET['ep']) ? $_GET['ep'] : '');
		}

		$name = isset($_GET['q']) ? $_GET['q'] : '';
		$relData = $releases->searchShows(
			$siteIdArr,
			$series,
			$episode,
			$airdate,
			$offset,
			$api->limit(),
			$name,
			$api->categoryID(),
			$maxAge,
			$minSize
		);

		$api->addLanguage($relData);
		$api->output($relData, $params, $outputXML, $offset, 'api');
		break;

	// Search movie releases.
	case 'm':
		$api->verifyEmptyParameter('q');
		$api->verifyEmptyParameter('imdbid');
		$maxAge = $api->maxAge();
		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);

		$imdbId = (isset($_GET['imdbid']) ? $_GET['imdbid'] : '-1');

		$relData = $releases->searchbyImdbId(
			$imdbId,
			$offset,
			$api->limit(),
			(isset($_GET['q']) ? $_GET['q'] : ''),
			$api->categoryID(),
			$maxAge,
			$minSize
		);

		$api->addCoverURL($relData,
			function($release) {
				return Misc::getCoverURL(['type' => 'movies', 'id' => $release['imdbid']]);
			}
		);

		$api->addLanguage($relData);
		$api->output($relData, $params, $outputXML, $offset, 'api');
		break;

	// Get NZB.
	case 'g':
		$api->verifyEmptyParameter('g');
		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$relData = $releases->getByGuid($_GET['id']);
		if ($relData) {
			header(
				'Location:' .
				WWW_TOP .
				'/getnzb?i=' .
				$uid .
				'&r=' .
				$apiKey .
				'&id=' .
				$relData['guid'] .
				((isset($_GET['del']) && $_GET['del'] == '1') ? '&del=1' : '')
			);
		} else {
			Misc::showApiError(300, 'No such item (the guid you provided has no release in our database)');
		}
		break;

	// Get individual NZB details.
	case 'd':
		if (!isset($_GET['id'])) {
			Misc::showApiError(200, 'Missing parameter (id is required for single release details)');
		}

		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$data = $releases->getByGuid($_GET['id']);

		$relData = [];
		if ($data) {
			$relData[] = $data;
		}
		$api->output($relData, $params, $outputXML, $offset, 'api');
		break;

	// Get an NFO file for an individual release.
	case 'n':
		if (!isset($_GET['id'])) {
			Misc::showApiError(200, 'Missing parameter (id is required for retrieving an NFO)');
		}

		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$rel = $releases->getByGuid($_GET["id"]);
		$data = $releases->getReleaseNfo($rel['id']);

		if ($rel !== false && !empty($rel)) {
			if ($data !== false) {
				if (isset($_GET['o']) && $_GET['o'] == 'file') {
					header("Content-type: application/octet-stream");
					header("Content-disposition: attachment; filename={$rel['searchname']}.nfo");
					exit($data['nfo']);
				} else {
					echo nl2br(Text::cp437toUTF($data['nfo']));
				}
			} else {
				Misc::showApiError(300, 'Release does not have an NFO file associated.');
			}
		} else {
			Misc::showApiError(300, 'Release does not exist.');
		}
		break;

	// Capabilities request.
	case 'c':
		$api->output('', $params, $outputXML, $offset, 'caps');
		break;
	// Register request.
	case 'r':
		$api->verifyEmptyParameter('email');

		if (!in_array((int)Settings::value('..registerstatus'), [Settings::REGISTER_STATUS_OPEN,
			Settings::REGISTER_STATUS_API_ONLY])) {
			Misc::showApiError(104);
		}
		// Check email is valid format.
		if (!$page->users->isValidEmail($_GET['email'])) {
			Misc::showApiError(106);
		}
		// Check email isn't taken.
		$ret = $page->users->getByEmail($_GET['email']);
		if (isset($ret['id'])) {
			Misc::showApiError(105);
		}
		// Create username/pass and register.
		$username = $page->users->generateUsername($_GET['email']);
		$password = $page->users->generatePassword();
		// Register.
		$userDefault = $page->users->getDefaultRole();
		$uid = $page->users->signUp(
			$username, $password, $_GET['email'], $_SERVER['REMOTE_ADDR'], $userDefault['id'], $userDefault['defaultinvites']
		);
		// Check if it succeeded.
		$userData = $page->users->getById($uid);
		if (!$userData) {
			Misc::showApiError(107);
		}

		$params['username'] = $username;
		$params['password'] = $password;
		$params['token'] = $userData['rsstoken'];

		$api->output('', $params, true, $offset, 'reg');
		break;
}
