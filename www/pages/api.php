<?php

use nzedb\Category;
use nzedb\Releases;
use nzedb\db\Settings;
use nzedb\utility\Misc;
use nzedb\Capabilities;


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
		case 'r':
		case 'register':
			$function = 'r';
			break;
		default:
			showApiError(202, 'No such function (' . $_GET['t'] . ')');
	}
} else {
	showApiError(200, 'Missing parameter (t)');
}

$uid = $apiKey = '';
$catExclusions = [];
$maxRequests = 0;
// Page is accessible only by the apikey, or logged in users.
if ($page->users->isLoggedIn()) {
	$uid = $page->userdata['id'];
	$apiKey = $page->userdata['rsstoken'];
	$catExclusions = $page->userdata['categoryexclusions'];
	$maxRequests = $page->userdata['apirequests'];
} else {
	if ($function != 'c' && $function != 'r') {
		if (!isset($_GET['apikey'])) {
			showApiError(200, 'Missing parameter (apikey)');
		}
		$res = $page->users->getByRssToken($_GET['apikey']);
		$apiKey = $_GET['apikey'];

		if (!$res) {
			showApiError(100, 'Incorrect user credentials (wrong API key)');
		}

		$uid = $res['id'];
		$catExclusions = $page->users->getCategoryExclusion($uid);
		$maxRequests = $res['apirequests'];
	}
}

$page->smarty->assign('uid', $uid);
$page->smarty->assign('rsstoken', $apiKey);

// Record user access to the api, if its been called by a user (i.e. capabilities request do not require a user to be logged in or key provided).
if ($uid != '') {
	$page->users->updateApiAccessed($uid);
	$apiRequests = $page->users->getApiRequests($uid);
	if ($apiRequests > $maxRequests) {
		showApiError(500, 'Request limit reached (' . $apiRequests . '/' . $maxRequests . ')');
	}
}

$releases = new Releases(['Settings' => $page->settings]);

$page->smarty->assign('extended', (isset($_GET['extended']) && $_GET['extended'] == 1 ? '1' : '0'));
$page->smarty->assign('del', (isset($_GET['del']) && $_GET['del'] == 1 ? '1' : '0'));


// Output is either json or xml.
$outputXML = (isset($_GET['o']) && $_GET['o'] == 'json' ? false : true);

switch ($function) {
	// Search releases.
	case 's':
		verifyEmptyParameter('q');
		$maxAge = maxAge();
		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$categoryID = categoryID();
		$limit = limit();
		$offset = offset();

		if (isset($_GET['q'])) {
			$relData = $releases->search(
				$_GET['q'], -1, -1, -1, -1, -1, 0, 0, -1, -1, $offset, $limit, '', $maxAge, $catExclusions,
				"basic", $categoryID
			);
		} else {
			$totalRows = $releases->getBrowseCount($categoryID, $maxAge, $catExclusions);
			$relData = $releases->getBrowseRange($categoryID, $offset, $limit, '', $maxAge, $catExclusions);
			if ($totalRows > 0 && count($relData) > 0) {
				$relData[0]['_totalrows'] = $totalRows;
			}
		}

		printOutput($relData, $outputXML, $page, $offset);
		break;

	// Search tv releases.
	case 'tv':
		verifyEmptyParameter('q');
		verifyEmptyParameter('rid');
		verifyEmptyParameter('season');
		verifyEmptyParameter('ep');
		$maxAge = maxAge();
		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$offset = offset();

		$relData = $releases->searchbyRageId(
			(isset($_GET['rid']) ? $_GET['rid'] : '-1'),
			(isset($_GET['season']) ? $_GET['season'] : ''),
			(isset($_GET['ep']) ? $_GET['ep'] : ''),
			$offset,
			limit(),
			(isset($_GET['q']) ? $_GET['q'] : ''),
			categoryID(),
			$maxAge
		);

		addLanguage($relData, $page->settings);
		printOutput($relData, $outputXML, $page, $offset);
		break;

	// Search movie releases.
	case 'm':
		verifyEmptyParameter('q');
		verifyEmptyParameter('imdbid');
		$maxAge = maxAge();
		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$offset = offset();

		$imdbId = (isset($_GET['imdbid']) ? $_GET['imdbid'] : '-1');

		$relData = $releases->searchbyImdbId(
			$imdbId,
			$offset,
			limit(),
			(isset($_GET['q']) ? $_GET['q'] : ''),
			categoryID(),
			$maxAge
		);

		addCoverURL($relData,
			function($release) {
				return Misc::getCoverURL(['type' => 'movies', 'id' => $release['imdbid']]);
			}
		);

		addLanguage($relData, $page->settings);
		printOutput($relData, $outputXML, $page, $offset);
		break;

	// Get NZB.
	case 'g':
		if (!isset($_GET['id'])) {
			showApiError(200, 'Missing parameter (id is required for downloading an NZB)');
		}

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
			showApiError(300, 'No such item (the guid you provided has no release in our database)');
		}
		break;

	// Get individual NZB details.
	case 'd':
		if (!isset($_GET['id'])) {
			showApiError(200, 'Missing parameter (id is required for downloading an NZB)');
		}

		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
		$data = $releases->getByGuid($_GET['id']);

		$relData = [];
		if ($data) {
			$relData[] = $data;
		}

		printOutput($relData, $outputXML, $page, offset());
		break;

	// Capabilities request.
	case 'c':
		//get categories
		$category = new Category(['Settings' => $page->settings]);
		$cats = $category->getForMenu();

		//insert cats into template variable
		$page->smarty->assign('parentcatlist', $cats);

		if ($outputXML) { //use apicaps.tpl if xml is requested
			$response = $page->smarty->fetch('apicaps.tpl');
			header('Content-type: text/xml');
			header('Content-Length: ' . strlen($response) );
			echo $response;
		} else { //otherwise construct array of capabilities and categories
			//get capabilities
			$caps = (new Capabilities(['Settings' => $page->settings]))->getForMenu();
			$caps['categories'] = $cats;
			//use json_encode
			$response = json_encode($caps);
			header('Content-type: application/json');
			header('Content-Length: ' . strlen($response) );
			echo $response;
		}
		break;
	// Register request.
	case 'r':
		verifyEmptyParameter('email');

		if (!in_array((int)$page->settings->getSetting('registerstatus'), [Settings::REGISTER_STATUS_OPEN, Settings::REGISTER_STATUS_API_ONLY])) {
			showApiError(104);
		}

		// Check email is valid format.
		if (!$page->users->isValidEmail($_GET['email'])) {
			showApiError(106);
		}

		// Check email isn't taken.
		$ret = $page->users->getByEmail($_GET['email']);
		if (isset($ret['id'])) {
			showApiError(105);
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
			showApiError(107);
		}

		$response =
			"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
			'<register username="' . $username .
			'" password="' . $password .
			'" apikey="' . $userdata['rsstoken'] .
			"\"/>\n";
		header('Content-type: text/xml');
		header('Content-Length: ' . strlen($response) );
		echo $response;
		break;
}

/**
 * Display error/error code.
 * @param int    $errorCode
 * @param string $errorText
 */
function showApiError($errorCode = 900, $errorText = '')
{
	if ($errorText === '') {
		switch ($errorCode) {
			case 100:
				$errorText = 'Incorrect user credentials';
				break;
			case 101:
				$errorText = 'Account suspended';
				break;
			case 102:
				$errorText = 'Insufficient privileges/not authorized';
				break;
			case 103:
				$errorText = 'Registration denied';
				break;
			case 104:
				$errorText = 'Registrations are closed';
				break;
			case 105:
				$errorText = 'Invalid registration (Email Address Taken)';
				break;
			case 106:
				$errorText = 'Invalid registration (Email Address Bad Format)';
				break;
			case 107:
				$errorText = 'Registration Failed (Data error)';
				break;
			case 200:
				$errorText = 'Missing parameter';
				break;
			case 201:
				$errorText = 'Incorrect parameter';
				break;
			case 202:
				$errorText = 'No such function';
				break;
			case 203:
				$errorText = 'Function not available';
				break;
			case 300:
				$errorText = 'No such item';
				break;
			case 500:
				$errorText = 'Request limit reached';
				break;
			case 501:
				$errorText = 'Download limit reached';
				break;
			default:
				$errorText = 'Unknown error';
				break;
		}
	}

	$response =
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
		'<error code="' . $errorCode .  '" description="' . $errorText . "\"/>\n";
	header('Content-type: text/xml');
	header('Content-Length: ' . strlen($response) );
	header('X-nZEDb: API ERROR [' . $errorCode . '] ' . $errorText);
	echo $response;
}

/**
 * Verify maxage parameter.
 * @return int
 */
function maxAge()
{
	$maxAge = -1;
	if (isset($_GET['maxage'])) {
		if ($_GET['maxage'] == '') {
			showApiError(201, 'Incorrect parameter (maxage must not be empty)');
		} elseif (!is_numeric($_GET['maxage'])) {
			showApiError(201, 'Incorrect parameter (maxage must be numeric)');
		} else {
			$maxAge = (int)$_GET['maxage'];
		}
	}
	return $maxAge;
}

/**
 * Verify cat parameter.
 * @return array
 */
function categoryID()
{
	$categoryID[] = -1;
	if (isset($_GET['cat'])) {
		$categoryIDs = urldecode($_GET['cat']);
		// Append Web-DL category ID if HD present for SickBeard / NZBDrone compatibility.
		if (strpos($categoryIDs, (string)Category::CAT_TV_HD) !== false &&
			strpos($categoryIDs, (string)Category::CAT_TV_WEBDL) === false) {
			$categoryIDs .= (',' . Category::CAT_TV_WEBDL);
		}
		$categoryID = explode(',', $categoryIDs);
	}
	return $categoryID;
}

/**
 * Verify limit parameter.
 * @return int
 */
function limit()
{
	$limit = 100;
	if (isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] < 100) {
		$limit = (int)$_GET['limit'];
	}
	return $limit;
}

/**
 * Verify offset parameter.
 * @return int
 */
function offset()
{
	$offset = 0;
	if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
		$offset = (int)$_GET['offset'];
	}
	return $offset;
}

/**
 * Print XML or JSON output.
 * @param array    $data   Data to print.
 * @param bool     $xml    True: Print as XML False: Print as JSON.
 * @param BasePage $page
 * @param int      $offset Offset for limit.
 */
function printOutput($data, $xml = true, $page, $offset = 0)
{
	if ($xml) {
		$page->smarty->assign('offset', $offset);
		$page->smarty->assign('releases', $data);
		$response = trim($page->smarty->fetch('apiresult.tpl'));
		header('Content-type: text/xml');
		header('Content-Length: ' . strlen($response) );
		echo $response;
	} else {
		$response = json_encode($data);
		header('Content-type: application/json');
		header('Content-Length: ' . strlen($response) );
		echo json_encode($data);
	}
}

/**
 * Check if a parameter is empty.
 * @param string $parameter
 */
function verifyEmptyParameter($parameter)
{
	if (isset($_GET[$parameter]) && $_GET[$parameter] == '') {
		showApiError(201, 'Incorrect parameter (' . $parameter . ' must not be empty)');
	}
}

function addCoverURL(&$releases, callable $getCoverURL)
{
	if ($releases && count($releases)) {
		foreach ($releases as $key => $release) {
			$coverURL = $getCoverURL($release);
			$releases[$key]['coverurl'] = $coverURL;
		}
	}
}

/**
 * Add language from media info XML to release search names.
 * @param array             $releases
 * @param nzedb\db\Settings $settings
 * @return array
 */
function addLanguage(&$releases, Settings $settings)
{
	if ($releases && count($releases)) {
		foreach ($releases as $key => $release) {
			if (isset($release['id'])) {
				$language = $settings->queryOneRow(sprintf('SELECT audiolanguage FROM audio_data WHERE releaseid = %d', $release['id']));
				if ($language !== false) {
					$releases[$key]['searchname'] = $releases[$key]['searchname'] . ' ' . $language['audiolanguage'];
				}
			}
		}
	}
}
