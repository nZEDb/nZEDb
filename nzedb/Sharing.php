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
  */

 /**
  * Sharing table columns.
  *
  * VARIOUS
  * ID           = The ID of the site.
  * local        = wether the site is local or not (1 for local 2 for not)
  *
  * LOCAL
  * lastpushtime = last time we posted metadata
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
  * lastarticle  = our newest fetched article #
  * lastdate     = the unixtime of the last article -> contained in the subject
  * firstarticle = our oldest fetched article #
  * firstdate    = the unixtime of the first article
  * real_name    = The name of the site, as sent by the site.
  * local_name   = The name of the site (a local name we can change).
  * notes        = We can add notes on this site.
  *
  * COMMENTS :
  * comments     = how many comments the non local site has
  * f_comments   = 1 = enable fetching comments (change this to a site setting ?) 0 disabled
  * p_comments   = post comments (also change this to a site setting?)
  * backfill_c   = our current backfill target (article number)
  * lasthash_c   = the hash of the last article -> contained in the subject
  * firsthash_c  = the hash of the oldest article
  *
  * METADATA :
  * f_sname      = Should we download this sites searchnames?
  * p_sname      = Should we upload our searchnames?
  * f_cat_id     = Should we download categoryID's from this site?
  * p_cat_id     = Should we upload our categoryID's?
  * f_imdb       = Should we download IMDB id's from this site?
  * p_imdb       = Should we upload our IMDB id's?
  * f_tvrage     = Should we download tvrage id's from this site?
  * p_tvrage     = Should we upload our tvrage id's?
  * backfill_m   = our current backfill target (article number)
  * lasthash_m   = the hash of the last article -> contained in the subject
  * firsthash_m  = the hash of the oldest article
  */

 /**
  * MySQL schema:
  *

DROP TABLE IF EXISTS sharing;
CREATE TABLE sharing (
	id    INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	local TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',

	lastpushtime DATETIME DEFAULT NULL,
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
	lastarticle  INT(11) UNSIGNED NOT NULL DEFAULT '0',
	lastdate     DATETIME DEFAULT NULL,
	firstarticle INT(11) UNSIGNED NOT NULL DEFAULT '0',
	firstdate    DATETIME DEFAULT NULL,
	real_name    VARCHAR(255) NOT NULL DEFAULT '',
	local_name   VARCHAR(255) NOT NULL DEFAULT '',
	notes        VARCHAR(255) NOT NULL DEFAULT '',

	comments     INT(11) UNSIGNED NOT NULL DEFAULT '0',
	f_comments   TINYINT(1) NOT NULL DEFAULT '0',
	p_comments   TINYINT(1) NOT NULL DEFAULT '0',
	backfill_c   INT(11) UNSIGNED NOT NULL DEFAULT '0',
	lasthash_c   VARCHAR(40) NOT NULL DEFAULT '',
	firsthash_c  VARCHAR(40) NOT NULL DEFAULT '',

	f_sname      TINYINT(1) NOT NULL DEFAULT '0',
	p_sname      TINYINT(1) NOT NULL DEFAULT '0',
	f_cat_id     TINYINT(1) NOT NULL DEFAULT '0',
	p_cat_id     TINYINT(1) NOT NULL DEFAULT '0',
	f_imdb       TINYINT(1) NOT NULL DEFAULT '0',
	p_imdb       TINYINT(1) NOT NULL DEFAULT '0',
	f_tvrage     TINYINT(1) NOT NULL DEFAULT '0',
	p_tvrage     TINYINT(1) NOT NULL DEFAULT '0',
	backfill_m   INT(11) UNSIGNED NOT NULL DEFAULT '0',
	lasthash_m   VARCHAR(40) NOT NULL DEFAULT '',
	firsthash_m  VARCHAR(40) NOT NULL DEFAULT '',
	PRIMARY KEY  (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

 */

 /**
  * Alterations to the comment table:

ALTER TABLE releasecomment ADD shared TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE releasecomment ADD shareid VARCHAR(40) NOT NULL DEFAULT '';

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
	 * The group to store/retrieve the articles.
	 *
	 * @note Not set in stone.
	 *
	 * @var constant string
	 * @access public
	 */
	const group = 'alt.binaries.zines';
	
	/**
	 * How many articles to download per loop.
	 * 
	 * @var constant int
	 * @access public
	 */
	const looparticles = 10000;

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
			($this->site->debuginfo == "0" && $echooutput) ? true : false;
*/
$this->debug = true;
		$this->echooutput = $echooutput;

		// Will be a site setting.. hides username when posting
		$this->hideuser = false;
		// Will be a site setting.. Auto enable sites?
		$this->autoenable = 1;
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

		$res = $this->db->query("SELECT id FROM releases r INNER JOIN releasecomment
			rc ON rc.nzb_guid = r.nzb_guid WHERE rc.releaseid = NULL");
		if (count($res) > 0) {
			foreach ($res as $row) {
				if ($this->db->queryExec(sprintf(
					"UPDATE releasecomment SET releaseid = %d", $row["id"]))
					!== false){
					$ret++;
				}
			}
		}
		return $ret;
	}


/* In post process it will send to this function and settings will be initiated. */
	/**
	 * Download all new metadata.
	 *
	 * @return array int How much of various new metadata have we downloaded?
	 *
	 * @access public
	 */
	public function retrieveAll() {
		$qty = array();

		$settings = $this->db->queryOneRow('SELECT * FROM sharing WHERE local = 1');
		if($settings === false) {

			$initiated = $this->initSite();
			if ($initiated === true) {
				$this->debugEcho('Sharing: Initiated new site settings.', 1,
				'retrieveAll');
			} else {
				$this->debugEcho('Sharing: Error trying to initiate site settings.', 2,
				'retrieveAll');
			}
		} else {
			if ($settings['override_f'] == '1') {
				$this->debugEcho('Fetching comments is disabled by the user.',
					1, 'retrieveAll');

				return $qty;
			}
			if ($settings["f_comments"] == '1') {
				return $this->scanForward($settings, $this->db);
			}
		}
		return $qty;
	}

	/**
	 * Upload all our new metadata.
	 *
	 * @return array int How much of various new metadata have we uploaded?
	 *
	 * @access public
	 */
	public function shareAll() {
		$qty = array();

		$settings = $this->db->queryOneRow("SELECT * FROM sharing WHERE local = 1");
		if ($settings === false) {

			$initiated = $this->initSite();
			if ($initiated === true) {
				$this->debugEcho('Initiated new site settings.', 1, 'shareAll');
			} else {
				$this->debugEcho('Error trying to initiate site settings.', 2 ,
				'shareAll');
			}
		} else {
			$new = false;
			if ($settings['lastpushtime'] == NULL) {
				$new = true;
			}
			// Metadata
//			$qty = $this->pushMetadata($settings, $new);
			// Comments
			$qty['comments'] = $this->pushComments($settings, $new);
		}
		return $qty;
	}

	/**
	 * Select new metadata that we should upload, upload them then update
	 * the DB to say they have been uploaded.
	 *
	 * @param array $settings The sharing table data.
	 * @param bool  $new      This is the first time we push metadata.
	 *
	 * @return int How many articles have been uploaded?
	 *
	 * @access protected
	 */
/*
	protected function pushMetadata($settings, $new) {
		$ret = 0;

		$last = $this->db->queryOneRow(
			"SELECT createddate AS d FROM releasecomment ORDER BY createddate LIMIT 1");

		if ($last === false) {
			return $ret;
		} else {
			if ($this->db->unix_timestamp($last["d"]) > $settings["lastpushtime"]) {

				$res = $this->db->query(sprintf(
					  "SELECT rc.*, r.nzb_guid, FROM releasecomment rc INNER JOIN"
					. " releases r ON r.id = rc.releaseid WHERE createddate > %s AND shared = 0"
					, $this->db->escapeString($last["d"])));

				if (count($res) > 0) {
					foreach ($res as $row) {
						$article = $this->encodeArticle($row, $settings);
						if ($article === false) {
							continue;
						} else {
							$stat = $this->pushArticle($body, $row);
							if ($stat === false) {
								continue;
							} else {
								$ret++;
								// Update DB to say we uploaded the comment.
								$this->db->queryExec("UPDATE releasecomment SET shared = 1");
							}
						}
					}
				}
			}
			return $ret;
		}
	}
*/

	/**
	 * Select new comments that we should upload, upload them then update
	 * the DB to say they have been uploaded.
	 *
	 * @param array $settings The sharing table data.
	 * @param bool  $new      This is the first time we push comments.
	 *
	 * @return int How many comments have been uploaded?
	 *
	 * @access protected
	 */
	protected function pushComments($settings, $new) {
		$ret = 0;

		$last = $this->db->queryOneRow(
			'SELECT createddate AS d FROM releasecomment ORDER BY createddate DESC LIMIT 1');

		if ($last === false) {
			$this->debugEcho('Could not select createddate from releasecomment.',
				2, 'pushComments');
			return $ret;
		}

		$res = array();
		if (!$new && $last['d'] > $settings['lastpushtime']) {

			$res = $this->db->query(sprintf(
				"SELECT rc.*, r.nzb_guid, u.username FROM releasecomment rc
				INNER JOIN releases r ON r.id = rc.releaseid
				INNER JOIN users u ON u.id = rc.userid 
				WHERE rc.createddate > %s AND rc.shared = 0"
				, $this->db->escapeString($last['d'])));
		} else {
			$res = $this->db->query(sprintf(
				"SELECT rc.*, r.nzb_guid, u.username FROM releasecomment rc 
				INNER JOIN releases r ON r.id = rc.releaseid 
				INNER JOIN users u ON u.id = rc.userid 
				WHERE rc.createddate > %s AND rc.shared = 0"
				, $this->db->escapeString($settings['firstuptime'])));
		}

		if (count($res) > 0) {
			$this->nntp->doConnect();
			foreach ($res as $row) {
				$body = $this->encodeArticle($row, $settings, true);
				if ($body === false) {
					continue;
				} else {
					if ($this->pushArticle($body, $row, 'c') === false) {
						continue;
					} else {
						$ret++;
						// Update DB to say we uploaded the comment.
						$this->db->queryExec(sprintf(
							'UPDATE releasecomment SET shared = 1 WHERE releaseid = %d',
							$row['releaseid']));
					}
				}
			}
			$this->nntp->doQuit();
		}
		return $ret;
	}

	/**
	 * Create an article body containing the metadata or comment and various other info.
	 *
	 * @param array $row      An array containg data from MySQL to form the article.
	 * @param array $settings The sharing table settings.
	 * @param bool  $comment  Is this for encoding a comment or metadata?
	 *
	 * @return string The json encoded document.
	 *
	 * @access protected
	 */
	protected function encodeArticle($row, $settings, $comment=false) {
		/* Example message for a comment:
		{
			"SITE": "nZEDb.521d7818435830.65093125",
			"NAME": "john's indexer",
			"GUID": "13781e319b79b1a19fec5ef4a931b163",
			"TIME": "1334663234",
			"COMMENT": "example",
			"CUSER": "john doe",
			"CDATE": "134234324"
			*CSHAREID: "bcd5a37c022525b62956e6975127f8c12a0bd4b5"
		}*/

		$type = '';
		$body = array();
		if ($comment) {
			$type = 'COMMENT';
			$body = $row['text'];
		} else {
			$type = 'META';
			$body = array(
				'IMDB'   => (
					($settings['p_imdb'] == '1' && $row['imdbid'] != NULL)
					? $row['imdbid'] : 'NULL'),
				'TVRAGE' => (
					($settings['p_tvrage'] == '1' && $row['rageid'] != NULL)
					? $row['rageid'] : 'NULL'),
				'CATID'  => (
					($settings['p_catid'] == '1' && $row['categoryid'] != '7010')
					? $row['categoryid'] : 'NULL'),
				'SNAME'  => (
					($settings['p_tvrage'] == '1' && $row['searchname'] != '')
					? $row['searchname'] : 'NULL')
				);
		}

		return json_encode(
				array(
					'SITE'     => $settings['real_name'],
					'NAME'     => $settings ['local_name'],
					'GUID'     => $row['nzb_guid'],
					'TIME'     => time(),
					$type      => $body,
					'CUSER'    => ($this->hideuser) ? "Anonymous" : $row["username"],
					'CDATE'    => $this->db->unix_timestamp($row["createddate"]),
					'CSHAREID' => $cshareid = sha1($comment.$row['nzb_guid'])
					));
	}

	/**
	 * GZIP an article body, then yEnc encode it, set up a subject, finally
	 * upload the comment.
	 *
	 * @param string $body The message to gzip/yEncode.
	 * @param array  $row  The comment/release info.
	 * @param string $type c for comment m for meta.
	 *
	 * @return bool  Have we uploaded the article?
	 *
	 * @access protected
	 */
	protected function pushArticle($body, $row, $type) {

		// Example subject (not set in stone) :
		// c_13781e319b79b1a19fec5ef4a931b163 - [1/1] "1334663234" (1/1) yEnc

		$success =
			$this->nntp->mail(
				// Group(s)
				self::group,
				// Subject
				$type . '_' . $row['nzb_guid'] . ' - [1/1] "' . time() . '" (1/1) yEnc',
				// Body
				$this->yenc->encode(gzdeflate($body, 4), uniqid('', true)),
				// From
				'From: <anon@anon.com>'
				);

		if ($success == false) {
			if(PEAR::isError($success)) {
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
	 * @return bool Did we decode and insert it?
	 *
	 * @access protected
	 */
	protected function decodeBody($body) {
		$message = gzinflate($body);
		if ($message !== false) {

			$m = json_decode($message, true);
			if (!isset($m["SITE"])) {
				return false;
			} else {
				// Check if we already have the site.
				$scheck = $db->queryOneRow(sprintf("SELECT id, status FROM
					sharing WHERE name = %s", $db->escapeString($m["SITE"])));

				if (isset($scheck["status"])) {
					$sitestatus = $scheck["status"];
				}

				if ($scheck === false) {
					$db->queryExec(sprintf(
						"INSERT INTO sharing (name, local, lastseen, firstseen,
						comments, status) VALUES (%s, 2, NOW(), NOW(), 1, %d)",
						$db->escapeString($m["SITE"]), $this->autoenable));

					$this->debugEcho('Inserted new site ' . $m['site'], 1,
					'decodeBody');

					$sitestatus = $this->autoenable;
				}

				// Only insert the comment if the site is enabled.
				if ($sitestatus == 1) {

					// Check if we already have the comment.
					$check = $db->queryOneRow(sprintf("SELECT id FROM releasecomment
					WHERE shareid = %s", $m["CSHAREID"]));
					if ($check === false) {
						$i = $db->queryExec(sprintf("INSERT INTO releasecomment
							(text, username, createddate, shareid, nzb_guid, site)
							VALUES (%s, %s, %s, %s, %s, %s)",
							$db->escapeString($m["BODY"]),
							$db->escapeString($m["CUSER"]),
							$db->from_unixtime($m["CDATE"]),
							$db->escapeString($m["CSHAREID"]),
							$message["GUID"],
							$db->escapeString($m["SITE"])));

						if ($i === false) {
							return false;
						} else {
							// Update the site.
							$db->queryExec(sprintf("UPDATE sharing SET lastseen
								= NOW(), comments = comments + 1 WHERE site = %s",
								$db->escapeString($m['SITE'])));
							return true;
						}
					} else {
						$this->debugEcho('We already have the comment with shareid '
							. $message['CSHAREID'], 1, 'decodeBody');
						return false;
					}
				} else {
					$this->debugEcho('We have skipped site  ' . $message['CSHAREID']
						. 'because the user has disabled it in their settings.', 1,
						'decodeBody');
					return false;
				}
			}
		} else {
			return false;
		}
	}

	// Download article headers from usenet until we find the last article. Then download the body, parse it.
	protected function scanForward($settings, $db) {
		$ret = 0;
		$this->nntp->doConnect();
		$data = $this->nntp->selectGroup(self::group);
		if(PEAR::isError($data)) {
			$data = $this->nntp->dataError($nntp, self::group);
			if ($data === false) {
				$this->debugEcho("Error selecting news group, error follows: "
						. $data->code . ' : ' . $data->message, 2, 'scanForward');
				return $ret;
			}
		}

		// Our newest article.
		$first = $settings["lastarticle"];
		// The servers newest article.
		$last = $data["last"];

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
			if(PEAR::isError($msgs)) {
				$this->nntp->doQuit();
				$this->nntp->doConnectNC();
				$this->nntp->selectGroup(self::group);
				$msgs = $this->nntp->getOverview($firstart . '-' . $lastart, true, false);
				if(PEAR::isError($msgs)) {
					$nntp->doQuit();
					$this->debugEcho("Error downloading article headers, error follows: "
						. $msgs->code . ' : ' . $msgs->message, 2, 'scanForward');
					return $ret;
				}
			}

			// We got the messages, filter through the subjects. Download new articles.
			if (is_array($msgs) && count($msgs) > 0) {
				$current = false;
				$msgids = array();
				foreach ($msgs as $msg) {
					/* The pattern : type_nzb_guid - [1/1] "unixtime" (1/1) yEnc */
					// Filter through headers.
					if (preg_match(
						'/^[cm]_([a-f0-9]{40}) - \[\d\/\d\] "(\d+)" \(\d\/\d\) yEnc$/'
						,$msg["Subject"], $matches)) {
						if ($matches[2] < $settings["lastdate"]) {
							continue;
						} else {
							if ($current === false) {
								if ($matches[1] == $settings["lasthash"]) {
									$current = true;
								}
								continue;
							} else {
								// Download article body using message-id.
								$body = $this->nntp->getMessage(self::group,
									$msg["Message-ID"]);
								// Continue if we don't receive the body.
								if ($body === false) {
//TODO -> Debug output.
									continue;
								} else {
									// Parse the body.
									if ($this->decodeBody === false) {
//TODO -> Debug output.
										continue;
									} else {
										$ret++;
									}
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
?>
