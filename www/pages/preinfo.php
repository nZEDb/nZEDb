<?php
/**
 * You can make this page accessible publicly by changing the nZEDb_PREINFO_OPEN setting in www/settings.php
 *
 * This page prints an XML (or JSON, see extras) on the browser with predb data based on criteria.
 *
 * NOTE: By default only 1 result is returned, see the Extras for returning more than 1 result.
 * NOTE: This page is only accessible by logged in users or users providing their API key.
 *       If you wish to make this open to anybody, you can change the if (true) to if (false) lower in this file.
 *
 * Main Parameter:
 * --------------
 *     type:
 *         This is used to select the search type. This is a mandatory parameter.
 *
 * Search Types:
 * ------------
 * These are the search types you can use: requestid, title, md5, sha1, category, all
 * They all have shorter versions, in order: r, t, m, s, c, a
 *
 *     requestid:
 *         This is an re-implementation of the http://predb_irc.nzedb.com/predb_irc.php?reqid=[REQUEST_ID]&group=[GROUP_NM]
 *
 *         Parameters:
 *         ----------
 *         reqid : The request ID
 *         group : The group name.
 *
 *         Example URL:
 *         -----------
 *         http://example.com/preinfo?type=requestid&reqid=123&group=alt.binaries.example
 *
 *     title:
 *         This loosely searches for a title (using like '%TITLE%').
 *         NOTE: The title MUST be encoded.
 *
 *         Parameters:
 *         ----------
 *         title: The pre title you are searching for.
 *
 *         Example URL:
 *         http://example.com/preinfo?type=title&title=debian
 *
 *     md5:
 *         Searches for a PRE using the provided MD5.
 *
 *         Parameters:
 *         ----------
 *         md5 : An MD5 hash.
 *
 *         Example URL:
 *         -----------
 *         http://example.com/preinfo?type=md5&md5=6e9552c9bd8e61c8f277c21220160234
 *
 *     sha1:
 *         Searches for a PRE using the provided SHA1.
 *
 *         Parameters:
 *         ----------
 *         sha1: An SHA1 hash.
 *
 *         Example URL:
 *         -----------
 *         http://example.com/preinfo?type=sha1&sha1=a6eb4d9d7f99ca47abe56f3220597663cf37ca4a
 *
 *     category:
 *         Search for PRE by category name.
 *
 *         Parameters:
 *         ----------
 *         category: Category name. ie: xxx
 *
 *         Example URL:
 *         -----------
 *         http://example.com/preinfo?type=category&category=xxx
 *
 *     all:
 *        Returns the newest pre(s). (see the Extras - limit option)
 *
 *        Example URL:
 *        -----------
 *        http://example.com/preinfo?t=all
 *
 * Extra Parameters:
 * ----------------
 *
 *     limit  : By default only 1 result is returned, you can pass limit to increase this (the max is 100).
 *     offset : Gets the next set of results when using limit.
 *              ie If you limit to 100, then you add offset 0, you get the 100 newest, offset 100, you would get the next 100 after the 100 newest and so on.
 *     json   : By default a XML is returned, you can pass json=1 to return in json. (anything else after json will return in XML)
 *     newer  : Search for pre's newer than this date (must be in unix time).
 *     older  : Search pre pre's older than this date (must be in unix time).
 *     nuked  : nuked=0 Means search pre's that have never been nuked. nuked=1 Means search pre's that are nuked, or have previously been nuked.
 *     apikey : The user's API key (from the profile page).
 *
 * Example URLs (using various parameters):
 * --------------------------------------
 *
 *     Returns the 10 newest pre's.
 *     http://example.com/preinfo?type=all&limit=10&apikey=227a0e58049d2e30efded245d0f447c8
 *
 *     Returns 25 pre's between april 1st and april 30th 2014.
 *     http://example.com/preinfo?type=all&limit=25&apikey=227a0e58049d2e30efded245d0f447c8&older=1398902399&newer=1396310400
 *
 *     Returns 1 pre with this MD5: 694dfdf3220bdb6219553262b8be37df
 *     http://example.com/preinfo?type=md5&md5=94dfdf3220bdb6219553262b8be37df&apikey=227a0e58049d2e30efded245d0f447c8
 *
 *     Returns 1 pre with this SHA1: e7782508663d40248ccaf1c9bd2a961348b2301b
 *     http://example.com/preinfo?type=sha1&sha1=e7782508663d40248ccaf1c9bd2a961348b2301b&apikey=227a0e58049d2e30efded245d0f447c8
 *
 *     Returns 1 pre with this requestid and group : 188247 alt.binaries.teevee
 *     http://example.com/preinfo?type=requestid&reqid=188247&group=alt.binaries.teevee&apikey=227a0e58049d2e30efded245d0f447c8
 *
 * POST:
 * -----
 *     You can also send a POST method with a serialized array of requests.
 *     An array with a key of 0 must be set. The other key's can be anything (the release ID for example).
 *     ident - Can be used to send an identifier to track the returned result. (ie: the release ID)
 *     group - This is the group name, alt.binaries.teevee for example.
 *     reqid - This is the request ID to lookup for the group. Must be numeric (int|string).
 *     Example: serialize(
 *                  array(
 *                       0 => array('ident' => 0, 'group' => 'none', 'reqid' => 0),
 *                       1723 => array('ident' => 1723, 'group' => 'alt.binaries.moovee', 'reqid' => 194293),
 *                       2384 => array('ident' => 2384, 'group' => 'alt.binaries.teevee', 'reqid' => 293823)
 *                  )
 *              )
 */

if (nZEDb_PREINFO_OPEN) {
	if (!$page->users->isLoggedIn()) {
		if (!isset($_GET['apikey'])) {
			apiError('Missing parameter (apikey)', 200);
		}

		if (!$page->users->getByRssToken($_GET['apikey'])) {
			apiError('Incorrect user credentials (api key is wrong)', 100);
		}
	}
}

$preData = array();
$json = false;
if (isset($_GET['json']) && $_GET['json'] == 1) {
	$json = true;
}
if (isset($_GET['type'])) {

	$limit = 1;
	if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
		$limit = $_GET['limit'];
		if ($limit > 100) {
			$limit = 100;
		}
	}

	$offset = 0;
	if (isset($_GET['offset']) && is_numeric($_GET['offset'])) {
		$offset = $_GET['offset'];
	}

	$newer = $older = $nuked = $category = '';
	if (isset($_GET['newer']) && is_numeric($_GET['newer'])) {
		$newer = ' AND p.predate > FROM_UNIXTIME(' . $_GET['newer'] . ') ';
	}

	if (isset($_GET['lower']) && is_numeric($_GET['lower'])) {
		$older = ' AND p.predate < FROM_UNIXTIME(' . $_GET['older'] . ') ';
	}

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
				$pdo = new nzedb\db\DB;
				$preData = $pdo->query(
					sprintf('
						SELECT p.*,
						g.name AS groupname
						FROM predb p
						INNER JOIN groups g ON g.id = p.group_id
						WHERE requestid = %d
						AND g.name = %s
						%s %s %s
						LIMIT %d
						OFFSET %d',
						$_GET['reqid'],
						$pdo->escapeString($_GET['group']),
						$newer,
						$older,
						$nuked,
						$limit,
						$offset
					)
				);
			}
			break;

		case 't':
		case 'title':
			if (isset($_GET['title'])) {
				$pdo = new nzedb\db\DB;
				$preData = $pdo->query(
					sprintf('SELECT * FROM predb p WHERE p.title %s %s %s %s LIMIT %d OFFSET %d',
						$newer,
						$older,
						$nuked,
						$pdo->likeString(urldecode($_GET['title'])),
						$limit,
						$offset
					)
				);
			}

			break;

		case 'm':
		case 'md5':
			if (isset($_GET['md5']) && strlen($_GET['title']) === 32) {
				$pdo = new nzedb\db\DB;
				$preData = $pdo->query(
					sprintf('SELECT * FROM predb p INNER JOIN predb_hashes ph ON ph.pre_id = p.id WHERE MATCH(hashes) AGAINST (%s) %s %s %s LIMIT %d OFFSET %d',
						$pdo->escapeString($_GET['md5']),
						$newer,
						$older,
						$nuked,
						$limit,
						$offset
					)
				);
			}
			break;

		case 's':
		case 'sha1':
			if (isset($_GET['sha1']) && strlen($_GET['sha1']) === 40) {
				$pdo = new nzedb\db\DB;
				$preData = $pdo->query(
					sprintf('SELECT * FROM predb p INNER JOIN predb_hashes ph ON ph.pre_id = p.id WHERE MATCH(hashes) AGAINST (%s) %s %s %s LIMIT %d OFFSET %d',
						$pdo->escapeString($_GET['sha1']),
						$newer,
						$older,
						$nuked,
						$limit,
						$offset
					)
				);
			}
			break;

		case 'c':
		case 'category':
			if (isset($_GET['category'])) {
				$pdo = new nzedb\db\DB;
				$preData = $pdo->query(
					sprintf('SELECT * FROM predb p WHERE p.category %s %s %s %s LIMIT %d OFFSET %d',
						$newer,
						$older,
						$nuked,
						$pdo->likeString($_GET['category']),
						$limit,
						$offset
					)
				);
			}
			break;

		case 'a':
		case 'all':
			$pdo = new nzedb\db\DB;
			$preData = $pdo->query(
				sprintf('SELECT * FROM predb p WHERE 1=1 %s %s %s ORDER BY p.predate DESC LIMIT %d OFFSET %d',
					$newer,
					$older,
					$nuked,
					$limit,
					$offset
				)
			);
			break;
	}
} else if (isset($_POST['data'])) {

	$reqData = @unserialize($_POST['data']);
	if ($reqData !== false && is_array($reqData) && isset($reqData[0]['ident'])) {
		$pdo = new nzedb\db\DB;
		$preData = array();

		foreach ($reqData as $request) {
			$result = $pdo->queryOneRow(
				sprintf('
					SELECT p.*,
					g.name AS groupname
					FROM predb p
					INNER JOIN groups g ON g.id = p.group_id
					WHERE requestid = %d
					AND g.name = %s
					LIMIT 1',
					$request['reqid'],
					$pdo->escapeString($request['group'])
				)
			);

			if ($result !== false) {
				$result['ident'] = $request['ident'];
				$preData[] = $result;
			}
		}
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
				' md5="'        . (!empty($data['md5'])        ? $data['md5']        : '') . '"',
				' sha1="'       . (!empty($data['sha1'])       ? $data['sha1']       : '') . '"',
				' nuked="'      . (!empty($data['nuked'])      ? $data['nuked']      : '') . '"',
				' category="'   . (!empty($data['category'])   ? $data['category']   : '') . '"',
				' source="'     . (!empty($data['source'])     ? $data['source']     : '') . '"',
				' nukereason="' . (!empty($data['nukereason']) ? $data['nukereason'] : '') . '"',
				' files="'      . (!empty($data['files'])      ? $data['files']      : '') . '"',
				' name="'       . (!empty($data['title'])      ? sanitize($data['title']) : '') . '"',
				' date="'       . (!empty($data['predate'])    ? strtotime($data['predate']) : '') . '"',
				' size="'       . (!empty($data['size']) && $data['size'] != 'NULL' ? $data['size'] : '') . '"',
				' group="'      . (isset($data['groupname']) && !empty($data['groupname']) ? $data['groupname'] : '' ) . '"',
				' ident="'      . (isset($data['ident']) && !empty($data['ident']) ? $data['ident'] : '') . '"',
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

// There's some weird encoding issues in some of the pre titles.
function sanitize($string)
{
	//return $string;
	return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '.', $string);
}
