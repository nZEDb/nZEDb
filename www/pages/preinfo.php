<?php
/**
 * You can enable this page by un-commenting case 'preinfo': in www\index.php
 *
 * This page prints an XML on the browser with predb data based on criteria.
 *
 * NOTE: By default only 1 result is returned, see the Extras for returning more than 1 result.
 *
 * Types:
 * -----
 * These are the search types you can use: requestid, title, md5, sha1, all
 * They all have shorter versions, in order: r, t, m, s, a
 *
 * requestid:
 *     This is an re-implementation of the http://predb_irc.nzedb.com/predb_irc.php?reqid=[REQUEST_ID]&group=[GROUP_NM]
 *
 *     Parameters:
 *     ----------
 *     reqid : The request ID
 *     group : The group name.
 *
 *     Example URL:
 *     -----------
 *     http://example.com/preinfo?t=requestid&reqid=123&group=alt.binaries.example
 *
 * title:
 *     This loosely searches for a title (using like '%TITLE%').
 *     NOTE: The title MUST be encoded.
 *
 *     Parameters:
 *     ----------
 *     title: The pre title you are searching for.
 *
 *     Example URL:
 *     http://example.com/preinfo?t=title&title=debian
 *
 * md5:
 *     Searches for a PRE using the provided MD5.
 *
 *     Parameters:
 *     ----------
 *     md5 : An MD5 hash.
 *
 *     Example URL:
 *     -----------
 *     http://example.com/preinfo?t=md5&md5=6e9552c9bd8e61c8f277c21220160234
 *
 * sha1:
 *     Searches for a PRE using the provided SHA1.
 *
 *     Parameters:
 *     ----------
 *     sha1: An SHA1 hash.
 *
 *     Example URL:
 *     -----------
 *     http://example.com/preinfo?t=sha1&sha1=a6eb4d9d7f99ca47abe56f3220597663cf37ca4a
 *
 * all:
 *    Returns the newest pre(s). (see the Extras - limit option)
 *
 *    Example URL:
 *    -----------
 *    http://example.com/preinfo?t=all
 *
 * Extras:
 * ------
 *
 *     limit : By default only 1 result is returned, you can pass limit to increase this (the max is 100).
 *     json  : By default a XML is returned, you can pass json=1 to return in json. (anything else after json will return in XML)
 *     newer : Search for pre's newer than this date (must be in unix time).
 *     older : Search pre pre's older than this date (must be in unix time).
 *     nuked : nuked=0 Means search pre's that have never been nuked. nuked=1 Means search pre's that are nuked, or have previously been nuked.
 */

// You can make this page accessible by all (even people without a API key) by setting this to false :
if (true) {
	$user = $uid = $apiKey = '';
	$maxRequests = 0;
	// Page is accessible only by the apikey, or logged in users.
	if ($users->isLoggedIn()) {
		$uid = $page->userdata['id'];
		$apiKey = $page->userdata['rsstoken'];
	} else {
		if ($function != 'c' && $function != 'r') {
			if ($page->site->registerstatus == Sites::REGISTER_STATUS_API_ONLY) {
				$res = $users->getById(0);
				$apiKey = '';
			} else {
				if (!isset($_GET['apikey'])) {
					apiError('Incorrect user credentials', 100);
				}
				$res = $users->getByRssToken($_GET['apikey']);
				$apiKey = $_GET['apikey'];
			}

			if (!$res) {
				if (!isset($_GET['apikey'])) {
					apiError('Incorrect user credentials', 100);
				}
			}

			$uid = $res['id'];
		}
	}

	$page->smarty->assign('uid', $uid);
	$page->smarty->assign('rsstoken', $apiKey);
}

$preData = array();
if (isset($_GET['type'])) {

	$limit = 1;
	if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
		$limit = $_GET['limit'];
		if ($limit > 100) {
			$limit = 100;
		}
	}

	$json = false;
	if (isset($_GET['json']) && $_GET['json'] == 1) {
		$json = true;
	}

	$newer = '';
	if (isset($_GET['newer']) && is_numeric($_GET['newer'])) {
		$newer = ' AND p.predate > FROM_UNIXTIME(' . $_GET['newer'] . ') ';
	}

	$older = '';
	if (isset($_GET['lower']) && is_numeric($_GET['lower'])) {
		$older = ' AND p.predate < FROM_UNIXTIME(' . $_GET['older'] . ') ';
	}

	$nuked = '';
	if (isset($_GET['nuked'])) {
		if ($_GET['nuked'] == 0) {
			$nuked = ' AND p.nuked = 0';
		} else if ($_GET['nuked'] == 1) {
			$nuked = ' AND p.nuked > 0';
		}
	}

	switch ($_GET['type']) {
		case 'r':
		case 'requestid':
			if (isset($_GET['reqid']) && is_numeric($_GET['reqid']) && isset($_GET['group']) && is_string($_GET['group'])) {
				$db = new nzedb\db\DB;
				$preData = $db->query(
					sprintf('
					SELECT p.*
					FROM predb p
					INNER JOIN groups g ON g.id = p.groupid
					WHERE requestid = %d
					AND g.name = %s
					%s %s %s
					LIMIT %d',
						$_GET['reqid'],
						$db->escapeString($_GET['group']),
						$newer,
						$older,
						$nuked,
						$limit
					)
				);
			}
			break;

		case 't':
		case 'title':
			if (isset($_GET['title'])) {
				$db = new nzedb\db\DB;
				$preData = $db->query(
					sprintf('SELECT * FROM predb p WHERE p.title %s %s %s LIKE %s LIMIT %d',
						$newer,
						$older,
						$nuked,
						$db->likeString($_GET['title']),
						$limit
					)
				);
			}

			break;

		case 'm':
		case 'md5':
			if (isset($_GET['md5']) && strlen($_GET['title']) === 32) {
				$db = new nzedb\db\DB;
				$preData = $db->query(
					sprintf('SELECT * FROM predb p WHERE p.md5 = %s %s %s %s LIMIT %d',
						$newer,
						$older,
						$nuked,
						$db->escapeString($_GET['md5']),
						$limit
					)
				);
			}
			break;

		case 's':
		case 'sha1':
			if (isset($_GET['sha1']) && strlen($_GET['sha1']) === 40) {
				$db = new nzedb\db\DB;
				$preData = $db->query(
					sprintf('SELECT * FROM predb p WHERE p.sha1 = %s %s %s %s LIMIT %d',
						$newer,
						$older,
						$nuked,
						$db->escapeString($_GET['sha1']),
						$limit
					)
				);
			}
			break;

		case 'a':
		case 'all':
			$db = new nzedb\db\DB;
			$preData = $db->query(
				sprintf('SELECT * FROM predb p WHERE 1=1 %s %s %s LIMIT %d',
					$newer,
					$older,
					$nuked,
					$limit
				)
			);
			break;
	}
}

if ($json === false) {
	header('Content-type: text/xml');
	echo
		'<?xml version="1.0" encoding="UTF-8"?>', PHP_EOL,
		'<requests>', PHP_EOL;

	if (count($preData) > 0) {
		foreach ($preData as $data) {
			echo
				'<request',
				' reqid="'      . (!empty($data['requestid'])  ? $data['requestid']  : '') . '"',
				' name="'       . (!empty($data['title'])      ? $data['title']      : '') . '"',
				' date="'       . (!empty($data['predate'])    ? $data['predate']    : '') . '"',
				' category="'   . (!empty($data['category'])   ? $data['category']   : '') . '"',
				' size="'       . (!empty($data['size'])       ? $data['size']       : '') . '"',
				' source="'     . (!empty($data['source'])     ? $data['source']     : '') . '"',
				' md5="'        . (!empty($data['md5'])        ? $data['md5']        : '') . '"',
				' sha1="'       . (!empty($data['sha1'])       ? $data['sha1']       : '') . '"',
				' nuked="'      . (!empty($data['nuked'])      ? $data['nuked']      : '') . '"',
				' nukereason="' . (!empty($data['nukereason']) ? $data['nukereason'] : '') . '"',
				' files="'      . (!empty($data['files'])      ? $data['files']      : '') . '"',
				'/>';
		}
	}

	echo '</requests>';
} else {
	header('Content-type: application/json');
	echo json_encode($preData);
}

function apiError($error, $code)
{
	header('Content-type: text/xml');
	exit(
		'<?xml version="1.0" encoding="UTF-8"?>' .
		PHP_EOL .
		'<error code="' .
		$code .
		'" description="' .
		$error .
		'"/>' .
		PHP_EOL
	);
}