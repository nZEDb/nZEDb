<?php
require_once nZEDb_LIBS . 'Yenc.php';

//define('CUR_PATH', realpath(dirname(__FILE__)));

 /**
  * @TODO:
  *
  * Create patches for comments table and sharing table
  *
  * Edit the schemas.
  *
  * Create a webpage in the admin section to administer everything.
  *
  * Add a backfill function.
  *
  * Fetch and post Metadata (imdbid / searchname / etc.)
  *
  * Encryption of body.
  *
  * Limit download / upload per run.
  *
  * On metadata, retard the time before downloading to leave people time to get
  *  the releases.
  *
  * When a release is edited on the site(searchname, imdbid, etc),
  *  reset shared to 0.
  *
  * Move local stuff to its own table.
  */

 /**
  * Sharing table columns.
  *
  * VARIOUS
  * ID           = The ID of the site.
  * local        = wether the site is local or not (1 for local 2 for not)
  *
  * LOCAL
  * firstuptime  = How far back should we upload metadata/comments the first time?
  * omlocal      = Oldest metadata we have locally posted (release id).
  * nmlocal      = Newest metadata we have locally posted (release id).
  * oclocal      = Oldest comment we have locally posted (comment id).
  * nclocal      = Newest comment we have locally posted (comment id).
  * autoenable   = Should we auto enable new sites?
  * hideuser     = Should we hide usernames when posting comments to usenet?
  * override_p   = Turn off all posting.
  * override_f   = Turn off all downloading.
  *
  * NON LOCAL :
  * updatetime   = last time a site was updated
  * status       = wether the non local site is enabled or not
  * lastseen     = last time we have seen the non local site
  * firstseen    = the first time we have seen the non local site
  * real_name    = The name of the site, as sent by the site.
  * local_name   = The name of the site (a local name we can change).
  * notes        = We can add notes on this site.
  *
  * COMMENTS :
  * lastptime_c  = last time we posted comments
  * comments     = how many comments the non local site has given us so far
  * f_comments   = 1 = enable fetching comments (change this to a site setting ?) 0 disabled
  * p_comments   = post comments (also change this to a site setting?)
  * lasthash_c   = the hash of the last article -> contained in the subject
  * firsthash_c  = the hash of the oldest article
  * lastart_c    = our newest fetched article # for the comment group
  * firstart_c   = our current oldest article # for the comment group
  * lastdate_c   = the unixtime of the last article -> contained in the subject
  * firstdate_c  = the unixtime of the first article
  *
  * METADATA :
  * lastptime_m  = last time we posted metadata
  * f_meta       = Turn off or on downloading of meta?
  * f_sname      = Should we download this sites searchnames?
  * p_sname      = Should we upload our searchnames?
  * f_cat_id     = Should we download categoryID's from this site?
  * p_cat_id     = Should we upload our categoryID's?
  * f_imdb       = Should we download IMDB id's from this site?
  * p_imdb       = Should we upload our IMDB id's?
  * f_tvrage     = Should we download tvrage id's from this site?
  * p_tvrage     = Should we upload our tvrage id's?
  * lasthash_m   = the hash of the last article -> contained in the subject
  * firsthash_m  = the hash of the oldest article
  * metatime   = The last time we locally uploaded, or downloaded from a remote site.
  * lastart_m    = our newest fetched article # for the meta group
  * firstart_m   = our current oldest article # for the meta group
  * lastdate_m   = the unixtime of the last article -> contained in the subject
  * firstdate_m  = the unixtime of the first article
  */

 /**
  * MySQL schema:
  *

DROP TABLE IF EXISTS sharing;
CREATE TABLE sharing (
	id    INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	local TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',

	firstuptime  DATETIME DEFAULT NULL,
	omlocal      INT(11) UNSIGNED NOT NULL DEFAULT '0',
	nmlocal      INT(11) UNSIGNED NOT NULL DEFAULT '0',
	oclocal      INT(11) UNSIGNED NOT NULL DEFAULT '0',
	nclocal      INT(11) UNSIGNED NOT NULL DEFAULT '0',
	autoenable   TINYINT(1) NOT NULL DEFAULT '0',
	hideuser     TINYINT(1) NOT NULL DEFAULT '0',
	override_p   TINYINT(1) NOT NULL DEFAULT '0',
	override_f   TINYINT(1) NOT NULL DEFAULT '0',

	updatetime   DATETIME DEFAULT NULL,
	status       TINYINT(1) NOT NULL DEFAULT '0',
	lastseen     DATETIME DEFAULT NULL,
	firstseen    DATETIME DEFAULT NULL,
	real_name    VARCHAR(255) NOT NULL DEFAULT '',
	local_name   VARCHAR(255) NOT NULL DEFAULT '',
	notes        VARCHAR(255) NOT NULL DEFAULT '',

	lastptime_c  DATETIME DEFAULT NULL,
	comments     INT(11) UNSIGNED NOT NULL DEFAULT '0',
	f_comments   TINYINT(1) NOT NULL DEFAULT '0',
	p_comments   TINYINT(1) NOT NULL DEFAULT '0',
	lasthash_c   VARCHAR(40) NOT NULL DEFAULT '',
	firsthash_c  VARCHAR(40) NOT NULL DEFAULT '',
	lastart_c    INT(11) UNSIGNED NOT NULL DEFAULT '0',
	firstart_c   INT(11) UNSIGNED NOT NULL DEFAULT '0',
	lastdate_c    DATETIME DEFAULT NULL,
	firstdate_c   DATETIME DEFAULT NULL,

	lastptime_m  DATETIME DEFAULT NULL,
	f_meta       TINYINT(1) NOT NULL DEFAULT '0',
	f_sname      TINYINT(1) NOT NULL DEFAULT '0',
	p_sname      TINYINT(1) NOT NULL DEFAULT '0',
	f_cat_id     TINYINT(1) NOT NULL DEFAULT '0',
	p_cat_id     TINYINT(1) NOT NULL DEFAULT '0',
	f_imdb       TINYINT(1) NOT NULL DEFAULT '0',
	p_imdb       TINYINT(1) NOT NULL DEFAULT '0',
	f_tvrage     TINYINT(1) NOT NULL DEFAULT '0',
	p_tvrage     TINYINT(1) NOT NULL DEFAULT '0',
	lasthash_m   VARCHAR(40) NOT NULL DEFAULT '',
	firsthash_m  VARCHAR(40) NOT NULL DEFAULT '',
	metatime     DATETIME DEFAULT NULL,
	lastart_m    INT(11) UNSIGNED NOT NULL DEFAULT '0',
	firstart_m   INT(11) UNSIGNED NOT NULL DEFAULT '0',
	lastdate_m    DATETIME DEFAULT NULL,
	firstdate_m   DATETIME DEFAULT NULL,
	PRIMARY KEY  (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

 */

 /**
  * Alterations to the comment table:

ALTER TABLE releasecomment ADD shared TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE releasecomment ADD shareid VARCHAR(40) NOT NULL DEFAULT '';
ALTER TABLE releasecomment ADD nzb_guid VARCHAR(40) NOT NULL DEFAULT '';
ALTER TABLE releasecomment ADD site VARCHAR(255) NOT NULL DEFAULT '';

 */

/**
 * Alteration to release table:

ALTER TABLE releases ADD shared TINYINT(1) NOT NULL DEFAULT '0';

*/

 /**
  * Class for sharing various atributes of a release to other nZEDb sites.
  *
  * @access public
  */
class Sharing {
	/**
	 * Debug priority.
	 * 0 - Turn it off.
	 * 1 - Non Important stuff.
	 * 2 - Important stuff.
	 * 3 - Everything.
	 *
	 * @var int
	 * @access private
	 */
	private $dpriority = 3;

	/**
	 * Max articles to download/upload the first time.
	 *
	 * @var constant int
	 * @access public
	 */
	const maxfirstime = 20000;

	/**
	 * Max comments to upload per run.
	 *
	 * @var constant int
	 * @access public
	 */
	const c_maxupload = 100;

	/**
	 * Max meta to upload per run.
	 *
	 * @var constant int
	 * @access public
	 */
	const m_maxupload = 100;

	/**
	 * How many articles to download per loop.
	 *
	 * @var constant int
	 * @access public
	 */
	const looparticles = 10000;

	/**
	 * The group to store/retrieve the articles for comments.
	 *
	 * @note Not set in stone.
	 *
	 * @var constant string
	 * @access public
	 */
	const c_group = 'alt.binaries.zines';

	/**
	 * The group to store/retrieve the articles for metadata.
	 *
	 * @note Not set in stone.
	 *
	 * @var constant string
	 * @access public
	 */
	const m_group = 'alt.binaries.wto';

	/**
	 * Instance of class DB.
	 *
	 * @var object
	 * @access private
	 */
	private $db;

	/**
	 * Instance of class NNTP.
	 *
	 * @var object
	 * @access private
	 */
	private $nntp;

	/**
	 * Instance of class Yenc.
	 *
	 * @var object
	 * @access private
	 */
	private $yenc;

	/**
	 * Instance of class site.
	 *
	 * @var object
	 * @access private
	 */
	private $s;

	/**
	 * Site settings.
	 *
	 * @var object
	 * @access private
	 */
	private $site;

	/**
	 * Display debug info to console?
	 *
	 * @var boolean
	 * @access private
	 */
	private $debug;

	/**
	 * Display general info to console?
	 *
	 * @var boolean
	 * @access private
	 */
	private $echooutput;

	/**
	 * Hide username of comment?
	 *
	 * @var boolean
	 * @access private
	 */
	private $hideuser;

	/**
	 * Auto enable nZEDb website ?
	 *
	 * @var int
	 * @access private
	 */
	private $autoenable;

	/**
	 * The sit settings.
	 *
	 * @var array
	 * @access private
	 */
	private $settings;

	/**
	 * Default constructor.
	 *
	 * @access public
	 */
	public function __construct($echooutput=false) {
		$this->db = new DB();
		$this->nntp = new NNTP();
		$this->yenc = new Yenc();
		$this->s = new Sites();
		$this->site = $this->s->get();
/*		$this->debug =
			($this->site->debuginfo == '0' && $echooutput) ? true : false;
*/
$this->debug = true;
		$this->echooutput = $echooutput;

		// Will be a site setting.. hides username when posting
		$this->hideuser = false;
		// Will be a site setting.. Auto enable sites?
		$this->autoenable = 1;
	}

/* In post process it will send to this function and settings will be initiated. */
	/**
	 * Download all new metadata.
	 *
	 * @return array int How much of various new comments/metadata have we downloaded?
	 *
	 * @access public
	 */
	public function retrieveAll() {
		$qty = array();
		$qty['c'] = $qty['m'] = 0;

		$this->settings = $this->db->queryOneRow('SELECT * FROM sharing WHERE local = 1');
		if($this->settings === false) {

			$initiated = $this->initSite();
			if ($initiated === true) {
				$this->debugEcho('Sharing: Initiated new site settings.', 1,
				'retrieveAll');
			} else {
				$this->debugEcho('Sharing: Error trying to initiate site settings.', 2,
				'retrieveAll');
			}
		} else {
			if ($this->settings['override_f'] == '1') {
				$this->debugEcho('Fetching comments/metadata is disabled by the user.',
					1, 'retrieveAll');

				return $qty;
			}
			if ($this->settings['f_comments'] == '1') {
				$qty['c'] = $this->scanForward(true);
			}
			if ($this->settings['f_meta'] == '1') {
				$qty['m'] = $this->scanForward();
			}
		}
		return $qty;
	}

	/**
	 * Upload all our new metadata.
	 *
	 * @return array int How much of various new comments/metadata have we uploaded?
	 *
	 * @access public
	 */
	public function shareAll() {
		$qty = array();
		$qty['c'] = $qty['m'] = 0;

		$this->settings = $this->db->queryOneRow('SELECT * FROM sharing WHERE local = 1');
		if ($this->settings === false) {

			$initiated = $this->initSite();
			if ($initiated === true) {
				$this->debugEcho('Initiated new site settings.', 1, 'shareAll');
			} else {
				$this->debugEcho('Error trying to initiate site settings.', 2 ,
				'shareAll');
			}
		} else {
			if ($this->settings['override_p'] == '1') {
				$this->debugEcho('Posting comments/metadata is disabled by the user.',
					1, 'shareAll');

				return $qty;
			}
			if ($this->settings['p_comments'] == '1') {
				$qty['c'] = $this->pushComments();
			}
			if ($this->settings['p_meta'] == '1') {
				$qty['m'] = $this->pushMetadata();
			}
		}
		return $qty;
	}

	/**
	 * Initiate local site settings for a first time run (or if the user
	 * resets his DB).
	 *
	 * @return bool If we were succesfull.
	 *
	 * @access protected
	 */
	protected function initSite() {
		$name = $this->db->escapeString(uniqid('nZEDb_', true));
		if ($this->db->queryExec(sprintf(
			"INSERT INTO sharing (
			local, lastpushtime, firstuptime,
			p_comments, p_sname, p_cat_id, p_imdb, p_tvrage,
			hideuser, autoenable, real_name , local_name)
			VALUES (1, NULL, NOW(),
			0, 0, 0, 0, 0,
			0, 0, %s, %s)", $name, $name)) !== false) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Try to match comments and releases together, setting the releases ID
	 * to the comment.
	 *
	 * @return int How many releases were matched to comments?
	 *
	 * @access protected
	 */
	protected function matchComments() {
		$ret = 0;

		$res = $this->db->query('SELECT id FROM releases r INNER JOIN releasecomment
			rc ON rc.nzb_guid = r.nzb_guid WHERE rc.releaseid = NULL');
		if (count($res) > 0) {
			foreach ($res as $row) {
				if ($this->db->queryExec(sprintf(
					"UPDATE releasecomment SET releaseid = %d", $row['id']))
					!== false){
					$ret++;
				}
			}
		}
		return $ret;
	}

	/**
	 * Select new metadata that we should upload, upload them then update
	 * the DB to say they have been uploaded.
	 *
	 * @return int How many articles have been uploaded?
	 *
	 * @access protected
	 */
	protected function pushMetadata() {
		$ret = 0;

		// If it's the first time we push, only push x amount of meta.
		$max = self::m_maxupload;
		if ($this->settings['lastptime_m'] == NULL) {
			$max = self::maxfirstime;
		}

		$this->nntp->doConnect();

		$perart = 50;
		while(true) {
			if ($ret >= $max) {
				break;
			}

			$res = $this->db->query(sprintf("SELECT id, nzb_guid, rageid, imdbid,
			categoryid, searchname FROM releases WHERE shared = 0
			ORDER BY adddate desc LIMIT %d", $perart));

			$qty = count($res);

			if ($qty > 0) {

				if ($this->echooutput) {
					echo "Sharing: Uploading {$qty} metadata to usenet.\n";
				}

				$body = $this->encodeArticle($res);
				if ($body === false) {
// Debug here?
					continue;
				}

				if ($this->pushArticle($body, 'm') === false) {
// Debug here?
					continue;
				} else {
					$ret += $qty;

					// Update DB to say we uploaded the comment.
					if ($qty > 1) {
						$ids = '';
						foreach ($res as $row) {
							$ids += $row['id'] . ',';
						}

						$this->db->queryExec(sprintf("UPDATE releases SET shared = 1
							WHERE id IN %s", $this->db->escapeString(rtrim($ids, ','))));
					} else {
						$this->db->queryExec(sprintf("UPDATE releases SET shared = 1
							WHERE id = %d", $res[0]['id']));
					}
				}
			} else {
				break;
			}

		}
		$this->nntp->doQuit();
		return $ret;
	}

	/**
	 * Select new comments that we should upload, upload them then update
	 * the DB to say they have been uploaded.
	 *
	 * @return int How many comments have been uploaded?
	 *
	 * @access protected
	 */
	protected function pushComments() {
		$ret = $total = 0;

		$last = $this->db->queryOneRow(
			'SELECT createddate AS d FROM releasecomment ORDER BY createddate DESC LIMIT 1');

		if ($last === false) {
			$this->debugEcho('Could not select createddate from releasecomment.',
				2, 'pushComments');
			return $ret;
		}

		$this->nntp->doConnect();

		$res = array();
		// How many comments to upload per article.
		$perart = 50;
		while (true) {

			$query = "SELECT rc.*, r.nzb_guid, u.username FROM releasecomment rc
					INNER JOIN releases r ON r.id = rc.releaseid
					INNER JOIN users u ON u.id = rc.userid
					WHERE rc.createddate > %s AND rc.shared = 0 LIMIT %d";

			// If this is the first time uploading comments.
			if ($this->settings['lastptime_c'] == NULL) {
				$res = $this->db->query(sprintf($query
					, $this->db->escapeString($this->settings['firstuptime'])
					, $perart));

			} else if ($last['d'] > $this->settings['lastptime_c']) {

				// Break if we uploaded more comments than max.
				if ($total >= self::c_maxupload) {
					break;
				}

				$res = $this->db->query(sprintf($query
					, $this->db->escapeString($last['d'])
					, $perart));
			}
			else {
				break;
			}

			$ccount = count($res);
			if ($ccount > 0) {

				$body = $this->encodeArticle($res, true);

				if ($body === false) {
// Debug here?
					continue;
				}

				if ($this->pushArticle($body, 'c') === false) {
// Debug here?
					continue;
				} else {
					$ret += $ccount;

					if ($ccount > 1) {
						// Update DB to say we uploaded the comment.
						$ids = '';
						foreach ($res as $row) {
							$ids += $row['releaseid'] . ',';
						}

						$this->db->queryExec(sprintf(
							"UPDATE releasecomment SET shared = 1 WHERE releaseid in %s"
							, $this->db->escapeString(rtrim($ids, ','))));
					} else {
						$this->db->queryExec(sprintf("UPDATE releasescomment SET
						shared = 1 WHERE releaseid = %d", $res[0]['releaseid']));
						break;
					}
				}
			} else {
				break;
			}
		}
		$this->nntp->doQuit();
		return $ret;
	}

	/**
	 * Create an article body containing the metadata or comment and various other info.
	 *
	 * @param array $res      All the rows of data returned from mysql.
	 * @param bool  $comment  Is this for encoding a comment or metadata?
	 *
	 * @return string The json encoded document.
	 *
	 * @access protected
	 */
	protected function encodeArticle($res, $comment=false) {
		/* Example message for comments:
		{
			"SITE":   "nZEDb.521d7818435830.65093125",
			"NAME":   "john's indexer",
			"TIME":   "1334663234",
			"COMMENTS":
				[
					{
					"USER":    "john doe",
					"DATE":    "134234324",
					"SHAREID": "bcd5a37c022525b62956e6975127f8c12a0bd4b5",
					"BODY":    "example"
					"GUID":    "13781e319b79b1a19fec5ef4a931b163",
					},
					{
					"USER":    "jane doe",
					"DATE":    "133234324",
					"SHAREID": "acd5a37c014525b62956e197512ff8c12b0bd475",
					"BODY":    "john doe smells"
					"GUID":    "234813319b7cbca12aeb5ef7af33b141",
					}
		}*/

		/* Example message for meta:
		{
			"SITE":   "nZEDb.521d7818435830.65093125",
			"NAME":   "john's indexer",
			"TIME":   "1334663234",
			"META":
				[
					{
					"IMDB":    "1408253",
					"TVRAGE":  "012",
					"CATID":   "3050",
					"SNAME":   "Ride_Along_(2014)_x264"
					"GUID":    "13781e319b79b1a19fec5ef4a931b163",
					},
					{
					"IMDB":    "394828",
					"TVRAGE":  "123",
					"CATID":   "3050",
					"SNAME":   "Lalalala"
					"GUID":    "234813319b7cbca12aeb5ef7af33b141",
					}
		}*/

		// Create an array containing the data the article will contain.
		$type = '';
		$body = array();
		if ($comment) {
			$type = 'COMMENTS';

			$iter = 0;
			foreach ($res as $row) {
				// Set the user as Anonymous if hide user is enabled.
				$body[$iter]['USER'] = ($this->hideuser) ? 'Anonymous' : $row['username'];
				$body[$iter]['CDATE'] = $this->db->unix_timestamp($row['createddate']);
				$body[$iter]['SHAREID'] = $cshareid = sha1($comment.$row['nzb_guid']);
				$body[$iter]['BODY'] = $row['text'];
				$body[$iter]['GUID'] = $row['nzb_guid'];

				$iter++;
			}

		} else {
			$type = 'META';

			$iter = 0;
			// Set these values to NULL if posting them was disabled.
			foreach ($res as $row) {
				$body[$iter]['IMDB'] = (
					($this->settings['p_imdb'] == '1' && $row['imdbid'] != NULL)
					? $row['imdbid'] : 'NULL');

				$body[$iter]['TVRAGE'] = (
					($this->settings['p_tvrage'] == '1' && $row['rageid'] != NULL)
					? $row['rageid'] : 'NULL');

				$body[$iter]['CATID'] = (
					($this->settings['p_cat_id'] == '1' && $row['categoryid'] != '7010')
					? $row['categoryid'] : 'NULL');

				$body[$iter]['SNAME']  = (
					($this->settings['p_tvrage'] == '1' && $row['searchname'] != '')
					? $row['searchname'] : 'NULL');

				$iter++;
			}
		}

		// Encode the array to JSON before returning.
		return json_encode(
				array(
					'SITE' => $this->settings['real_name'],
					'NAME' => $this->settings ['local_name'],
					'TIME' => time(),
					$type  => $body));
	}

	/**
	 * GZIP an article body, then yEnc encode it, set up a subject, finally
	 * upload the comment.
	 *
	 * @param string $body The message to gzip/yEncode.
	 * @param string $type c for comment m for meta.
	 *
	 * @return bool  Have we uploaded the article?
	 *
	 * @access protected
	 */
	protected function pushArticle($body, $type) {

		// Example subject (not set in stone) :
		// nZEDb.521d7818435830.65093125_c - [1/1] "1334663234" (1/1) yEnc

		// Try uploading the article to usenet.
		$success =
			$this->nntp->mail(
				// Group(s)
				($type === 'c') ? self::c_group : self::m_group,
				// Subject
				$this->settings['real_name']
				. '_' . $type . '_' . ' - [1/1] "' . time() . '" (1/1) yEnc',
				// Body
				$this->yenc->encode(gzdeflate($body, 4), uniqid('', true)),
				// From
				'From: <anon@anon.com>'
				);

		if ($success == false) {
			if($this->nntp->isError($success)) {
				$this->debugEcho('Error uploading comment to usenet, error follows: '
				. $success->code . ' : ' . $success->message, 2, 'pushArticle');
			}
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Decodes an article body and inserts the content into the DB.
	 *
	 * @param string $body The body to decode.
	 *
	 * @return int How many comments or meta have we inserted.
	 *
	 * @access protected
	 */
	protected function decodeBody($body, $comment=false) {
		$ret = 0;
		$message = gzinflate($body);
		if ($message !== false) {

			$m = json_decode($message, true);
			if (!isset($m['SITE'])) {
				return $ret;
// Debug here?
			} else {
				// Check if we already have the site.
				$scheck = $this->db->queryOneRow(sprintf("SELECT id, status FROM
					sharing WHERE name = %s", $this->db->escapeString($m['SITE'])));

				// Check if the site is enabled.
				if (isset($scheck['status'])) {
					$sitestatus = $scheck['status'];
				}

				// If we don't have the site, insert it.
				if ($scheck === false) {
					$this->db->queryExec(sprintf(
						"INSERT INTO sharing (name, local, lastseen, firstseen,
						metatime, comments, status) VALUES
						(%s, 2, NOW(), NOW(), NOW(), 1, %d)",
						$this->db->escapeString($m['SITE']), $this->autoenable));

					$this->debugEcho('Inserted new site ' . $m['site'], 1,
					'decodeBody');

					// Check if we have auto site enabling on.
					$sitestatus = $this->autoenable;
				}

				// Only insert the comment/metadata if the site is enabled.
				if ($sitestatus == 1) {

					// Is it a comment or metadata?
					if ($comment === true) {

						// Loop through the comments.
						foreach ($m['COMMENTS'] as $c) {

							// Check if we already have the comment.
							$check = $this->db->queryOneRow(sprintf("SELECT id FROM
								releasecomment WHERE shareid = %s", $c['SHAREID']));
							if ($check === false) {

								// Try to insert the comment.
								$i = $this->db->queryExec(sprintf("INSERT INTO releasecomment
									(text, username, createddate, shareid, nzb_guid, site)
									VALUES (%s, %s, %s, %s, %s, %s)",
									$this->db->escapeString($c['BODY']),
									$this->db->escapeString($c['USER']),
									$this->db->from_unixtime($c['DATE']),
									$this->db->escapeString($c['SHAREID']),
									$this->db->escapeString($c['GUID']),
									$this->db->escapeString($m['SITE'])));

								if ($i === false) {
// Debug here?
								} else {
									$ret++;
								}
							} else {
								$this->debugEcho(
									'We already have the comment with shareid '
									. $c['SHAREID'], 1, 'decodeBody');
							}
						}
						// Update the site.
						$this->db->queryExec(sprintf("
						UPDATE sharing SET
							lastseen = NOW(),
							comments = comments + %d
						WHERE site = %s",
						$ret,
						$this->db->escapeString($m['SITE'])));

					// This is metadata, update the release..
					} else {
						foreach ($m['META'] as $d) {
							$i = $this->db->queryExec(sprintf("
								UPDATE releases SET
									imdbid = %s,
									rageid = %s,
									categoryid = %s,
									searchname = %s
								WHERE nzb_guid = %s",
								$this->db->escapeString($d['IMDB']),
								$this->db->escapeString($d['TVRAGE']),
								$this->db->escapeString($d['CATID']),
								$this->db->escapeString($d['SNAME']),
								$this->db->escapeString($d['GUID'])));

							if ($i === false) {
// Debug here?
							} else {
								$ret++;
							}
						}

					// Update the site.
					$this->db->queryExec(sprintf("UPDATE sharing SET
						lastseen = NOW() WHERE site = %s",
						$this->db->escapeString($m['SITE'])));
					}

				} else {
					$this->debugEcho('We have skipped site  ' . $m['CSHAREID']
						. 'because the user has disabled it in their settings.', 1,
						'decodeBody');
				}
			}
		}
		return $ret;
	}

	/**
	 * Download article headers from usenet until we find the last article.
	 * Then download the body, parse it.
	 *
	 * @param bool  $comments Are we looking for comments or metadata?
	 *
	 * @return int How many meta/comments have we fetched.
	 *
	 * @access protected.
	 */
	protected function scanForward($comments=false) {
		$ret = 0;

		// Comments and meta have their respective groups.
		$group = self::m_group;
		// Our newest article.
		$first = $this->settings['lastarticle_m'];

		if ($comments) {
			$group = self::c_group;
			$first = $this->settings['lastarticle_c'];
		}

		$this->nntp->doConnect();
		$data = $this->nntp->selectGroup($group);
		if($this->nntp->isError($data)) {
			$data = $this->nntp->dataError($nntp, $group);
			if ($data === false) {
				$this->debugEcho('Error selecting news group, error follows: '
						. $data->code . ' : ' . $data->message, 2, 'scanForward');
				return $ret;
			}
		}

		// The servers newest article.
		$last = $data['last'];

		$under = $subs = $done = false;
		$lastart = $firstart = 0;
		$art = $this->looparticles;
		while ($done === false) {
			// First run. Do 10000 articles max at a time.
			if ($subs === false && $last - $first > $art) {
				$subs = true;
				// The newest article we want.
				$lastart = $last;
				// The oldest article we want.
				$firstart = $last - $art;
			} else if ($subs === false && $last - $first <= $art) {
				$lastart = $last;
				$firstart = $first;
				$under = true;
			}
			// Subsequent runs.
			else if ($lastart - $first > $art) {
				if ($firstart - $first <= $art) {
					$under = true;
					$lastart = $lastart - $art;
					$firstart = $first;
				} else {
					$lastart = $lastart - $art;
					$firstart = $lastart - $art;
				}
			}
			$this->debugEcho('The newest article we want:' . $lastart .
				"\nThe oldest article we want: " . $firstart, 1, 'scanForward');

			// Start downloading headers.
			$msgs = $this->nntp->getOverview($firstart . '-' . $lastart, true, false);
			if($this->nntp->isError($msgs)) {
				$this->nntp->doQuit();
				$this->nntp->doConnect(false);
				$this->nntp->selectGroup($group);
				$msgs = $this->nntp->getOverview($firstart . '-' . $lastart, true, false);
				if($this->nntp->isError($msgs)) {
					$nntp->doQuit();
					$this->debugEcho('Error downloading article headers, error follows: '
						. $msgs->code . ' : ' . $msgs->message, 2, 'scanForward');
					return $ret;
				}
			}

			// We got the messages, filter through the subjects. Download new articles.
			if (is_array($msgs) && count($msgs) > 0) {

				$msgids = array();
				foreach ($msgs as $msg) {
					/* The pattern : sitename_type - [1/1] "unixtime" (1/1) yEnc */
					//nZEDb.521d7818435830.65093125_c - [1/1] "1334663234" (1/1) yEnc
					// Filter through headers.
					if (preg_match(
						'/^(?P<site>nZEDb\.\d+\.\d+)_(?P<type>[cm]) - \[\d\/\d\] "(?P<time>\d+)" \(\d\/\d\) yEnc$/'
						,$msg['Subject'], $matches)) {

						// Check if the article is older than our newest for this site.
						$ncheck = $this->db->queryOneRow(sprint("SELECT lastdate FROM
							sharing WHERE real_name = %s", $matches['site']));

						if ($ncheck !== false && ($matches['time'] < $ncheck['time'])) {
							// This means our local fetched is newer, so ignore.
							continue;
						} else {

							// Download article body using message-id.
							$body = $this->nntp->getMessage($group,
								$msg['Message-ID']);
							// Continue if we don't receive the body.
							if ($body === false) {
//TODO -> Debug output.
								continue;
							} else {
								// Parse the body.
								$total = $this->decodeBody($body, $comments);
								if ($total === false) {
//TODO -> Debug output.
									continue;
								} else {
									$ret += $total;
								}
							}
						}
					}
				}
			} else {
				// Nntp didnt return anything?
//TODO -> Debug output.
				continue;
			}
			// Done so break out
			if ($under === true || $firstart <= $first) {
				break;
			}
		}
		$this->nntp->doQuit();
		return $ret;
	}

	/**
	 * Echo debug output.
	 *
	 * @param string $string   The text to output.
	 * @param int    $priority The priority the text has.
	 * @param string $function The name of the function.
	 *
	 * @return void
	 *
	 * @access protected
	 */
	protected function debugEcho($string, $priority, $function) {
		if (!$this->debug || ($this->dpriority < $priority ||
			$this->dpriority > 3 || $this->dpriority < 0)) {
			return;
		} else {
			$message = 'DEBUG: nZEDb.Sharing.' . $function . '() [' . $string . "]\n";
			if ($this->dpriority === 3) {
				echo $message;
			} else if ($this->dpriority === 2 && $priority === 2) {
				echo $message;
			} else if ($this->dpriority === 1 && $priority === 1) {
				echo $message;
			}
		}
	}
}
