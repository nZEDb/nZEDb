<?php
require_once nZEDb_LIBS . 'Yenc.php';

define('CUR_PATH', realpath(dirname(__FILE__)));

/* Class for sharing comments.
 *
 * TODO:
 *
 * Create sharing table.
 * 		see function initSite()
 *
 * Create column shared in releasecomments.
 * 		shared = wether we shared the comment previously.
 * Create column shareid in releasecomments.
 * 		shareid = sha1 hash of comment+guid
 * Create column username in releasecomments.
 * 		username = the name of the user who posted the comment
 * Create column nzb_guid in releasecomments.
 * 		nzb_guid = the md5 hash of the first message-id in a nzb file
 *
 * Add site settings to DB.
 * 		something to toggle on and off the whole sharing system.
 * 		option to hide usernames
 * 		option to auto enable sites
 *
 * Add a backfill function.
 *
 */
class Sharing
{
	function __construct($echooutput=false)
	{
		$s = new Sites();
		$this->site = $s->get();
		$this->debug = ($this->site->debuginfo == "0") ? false : true;
		$this->echooutput = $echooutput;
		$this->db = new DB();

		// Will be a site setting.. hides username when posting
		$this->hideuser = false;
		// Will be a site setting.. Auto enable sites?
		$this->autoenable = 1;

		// Placeholder group.
		$this->group = "alt.binaries.zines";
	}

	// For first run, initiate the site settings.
	function initSite()
	{
		/* updatetime	= last time a site was updated
		 * backfill		= our current backfill target
		 * name 		= the name of the site
		 * local		= wether the site is local or not (1 for local 2 for not loca)
		 * status		= wether the non local site is enabled or not.
		 * lastseen		= last time we have seen the non local site
		 * comments		= how many comments the non local site has
		 * firstseen	= the first time we have seen the non local site
		 * f_comment	= 1 = enable fetching comments (change this to a site setting ?) 0 disabled
		 * p_comments	= post comments (also change this to a site setting?)
		 * lastpushtime	= last time we posted a comment
		 * lasthash		= the hash of the last article -> contained in the subject
		 * lastarticle	= our newest fetched article #
		 * lastdate		= the unixtime of the last article -> contained in the subject
		 * firsthash	= the hash of the oldest article
		 * firstarticle	= our oldest fetched article #
		 * firstdate	= the unixtime of the first article
		 */

		$db = $this->db;
		$t = $db->queryExec(sprintf("INSERT INTO sharing (updatetime, backfill, name, local, status, lasteen, comments, firstseen, f_comments, p_comments, lastpushtime, lasthash, lastarticle, lastdate, firsthash, firstarticle, firstdate) VALUES (NOW(), 0, %s, 1, 0, 0, NULL, NULL, NULL, NULL, NULL))", $db->escapeString(uniqid("nZEDb.", true))));
		if ($t !== false)
			return true;
		else
			return false;
	}

	// Match comments to releases.
	function matchComments()
	{
		$db = $this->db;
		$ret = 0;

		$res = $db->query("SELECT id FROM releases r INNER JOIN releasecomment rc ON rc.nzb_guid = r.nzb_guid WHERE rc.releaseid = NULL");
		if (count($res) > 0)
		{
			foreach ($res as $row)
			{
				$i = $db->queryExec(sprintf("UPDATE releasecomment SET releaseid = %d", $row["id"]));
				if ($i !== false)
					$ret++;
			}
		}
		return $ret;
	}


/* In post process it will send to this function and settings will be initiated. */
	// Retrieve new content.
	public function retrieveAll()
	{
		$db = $this->db;

		$settings = $db->queryOneRow("SELECT * FROM sharing WHERE local = 1");
		if($settings === false)
		{
			if ($this->echooutput)
			{
				if ($this->init() === true)
					echo "Sharing: Initiated new site settings.\n";
				else
					echo "Sharing: Error trying to initiate site settings.\n";

				return 0;
			}
		}
		else
		{
			if ($settings["f_comments"] == 1)
				return $this->scanForward($settings, $db);
		}
	}

	// Upload new content.
	public function shareAll()
	{
		$db = $this->db;

		$settings = $db->queryOneRow("SELECT * FROM sharing WHERE local = 1");
		if ($settings === false)
		{
			if ($this->echooutput)
			{
				if ($this->init() === true)
					echo "Sharing: Initiated new site settings.\n";
				else
					echo "Sharing: Error trying to initiate site settings.\n";

				return 0;
			}
		}
		else
		{
			if ($settings["p_comments"] == 1)
				return $this->push($settings, $db);
		}
	}

	// Select new comments that are ready to upload.
	function push($settings, $db)
	{
		$ret = 0;
		$last = $db->queryOneRow("SELECT createdate AS d FROM releasecomments ORDER BY createdate LIMIT 1");
		if ($last === false)
			return $ret;
		else
		{
			if ($db->unix_timestamp($last["d"]) > $settings["lastpushtime"])
			{
				$res = $db->query(sprintf("SELECT rc.*, r.nzb_guid, FROM releasecomment rc INNER JOIN releases r ON r.id = rc.releaseid WHERE createdate > %s AND shared = 0", $db->escapeString($last["d"])));
				if (count($res) > 0)
				{
					foreach ($res as $row)
					{
						$article = $this->encodeArticle($row, $settings);
						if ($article === false)
							continue;
						else
						{
							$stat = $this->pushArticle($body, $row);
							if ($stat === false)
								continue;
							else
							{
								$ret++;
								// Update DB to say we uploaded the comment.
								$db->queryExec("UPDATE releasecomments SET shared = 1");
							}
						}
					}
				}
			}
			return $ret;
		}
	}

	// gzip then yEnc encode the body, set up the subject then attempt to upload the comment.
	function pushArticle($body, $row)
	{
		$yenc = new Yenc;
		$nntp = new NNTP();
		$nntp->doConnect();
		// group(s),                   subject                          ,            body                           , poster
		$success = $nntp->post($this->group, $row['nzb_guid'].' - [1/1] "'.time().'" (1/1) yEnc', $yenc->encode(gzdeflate($body, 4), uniqid), "nZEDb");
		$nntp->doQuit();
		if ($success == false)
			return false;
		else
			return true;

	}

	// Create a message containing the details we want to upload.
	function encodeArticle($row, $settings)
	{
		/* Example message:
		{
			"SITE": "nZEDb.521d7818435830.65093125",
			"GUID": "13781e319b79b1a19fec5ef4a931b163",
			"TIME": "1334663234",
			"COMMENT": "example",
			"CUSER": "john doe",
			"CDATE": "134234324"
			*CSHAREID: "bcd5a37c022525b62956e6975127f8c12a0bd4b5"
		}*/

		//$site = $settings["name"];
		$site = 'nZEDb.521d7818435830.65093125';

		//$guid = $row['nzb_guid'];
		$guid = '13781e319b79b1a19fec5ef4a931b163';

		//$comment = $row["text"];
		$comment = "Testing uploading comments to usenet.";

		/*
		if ($this->hideuser)
			$cuser = "Anonymous User";
		else
			$cuser = $row["username"];*/
		$cuser = "John Doe";

		//$cdate = $db->unix_timestamp($row["createdate"]);
		$cdate = "1377797670";

		//$cshareid = sha1($comment.$guid);
		$cshareid = "a30c7201057fb208a1653f91c05d172bbfc096f1";

		return json_encode(array('SITE' => $site, 'GUID' => $guid, 'TIME' => time(), 'COMMENT' => $comment, 'CUSER' => $cuser, 'CDATE' => $cdate, 'CSHAREID' => $cshareid));
	}

	// Decode a downloaded message and insert it.
	function decodeBody($body)
	{
		$message = gzinflate($body);
		if ($message !== false)
		{
			$m = json_decode($message, true);
			{
				if (!isset($m["SITE"]))
					return false;
				else
				{
					// Check if we already have the site.
					$scheck = $db->queryOneRow(sprintf("SELECT id, status FROM sharing WHERE name = %s", $db->escapeString($m["SITE"])));

					if (isset($scheck["status"]))
						$sitestatus = $scheck["status"];

					if ($scheck === false)
					{
						$db->queryExec(sprintf("INSERT INTO sharing (name, local, lastseen, firstseen, comments, status) VALUES (%s, 2, NOW(), NOW(), 1, %d)", $db->escapeString($m["SITE"]), $this->autoenable));
						if ($this->debug)
							echo "Inserted new site ".$m["site"];

						$sitestatus = $this->autoenable;
					}

					// Only insert the comment if the site is enabled.
					if ($sitestatus == 1)
					{
						// Check if we already have the comment.
						$check = $db->queryOneRow(sprintf("SELECT id FROM releasecomment WHERE shareid = %s", $m["CSHAREID"]));
						if ($check === false)
						{
							$i = $db->queryExec(sprintf("INSERT INTO releasecomment (text, username, createdate, shareid, nzb_guid, site) VALUES (%s, %s, %s, %s, %s, %s)", $db->escapeString($m["COMMENT"]), $db->escapeString($m["CUSER"]), $db->from_unixtime($m["CDATE"]), $db->escapeString($m["CSHAREID"]), $message["GUID"], $db->escapeString($m["SITE"])));
							if ($i === false)
								return false;
							else
							{
								// Update the site.
								$db->queryExec(sprintf("UPDATE sharing SET lastseen = NOW(), comments = comments + 1 WHERE site = %s", $db->escapeString($m['SITE'])));
								return true;
							}
						}
						else
						{
							if ($this->debug)
								echo "We already have the comment with shareid ".$message["CSHAREID"];
							return false;
						}
					}
					else
					{
						if ($this->debug)
							echo "We have skipped site ".$message["CSHAREID"]." because the user has disabled it in their settings.\n";
						return false;
					}
				}
			}
		}
		else
			return false;
	}

	// Download article headers from usenet until we find the last article. Then download the body, parse it.
	function scanForward($settings, $db)
	{
		$ret = 0;
		$nntp = new Nntp;
		$nntp->doConnect();
		$group = $this->group;
		$data = $nntp->selectGroup($group);
		if(PEAR::isError($data))
		{
			$data = $nntp->dataError($nntp, $group);
			if ($data === false)
				return $ret;
		}
		$nntp->doQuit();

		// Our newest article.
		$first = $settings["lastarticle"];
		// The servers newest article.
		$last = $data["last"];

		$under = $subs = $done = false;
		$lastart = $firstart = 0;
		$art = 10000;
		while ($done === false)
		{
			// First run. Do 10000 articles max at a time.
			if ($subs === false && $last - $first > $art)
			{
				$subs = true;
				// The newest article we want.
				$lastart = $last;
				// The oldest article we want.
				$firstart = $last - $art;
			}
			else if ($subs === false && $last - $first <= $art)
			{
				$lastart = $last;
				$firstart = $first;
				$under = true;
			}
			// Subsequent runs.
			else if ($lastart - $first > $art)
			{
				if ($firstart - $first <= $art)
				{
					$under = true;
					$lastart = $lastart - $art;
					$firstart = $first;
				}
				else
				{
					$lastart = $lastart - $art;
					$firstart = $lastart - $art;
				}
			}
			if ($this->debug && $this->echooutput)
				echo "The newest article we want: ".$lastart."\nThe oldest article we want: ".$firstart."\n";

			// Start downloading headers.
			$nntp->doConnect();
			$nntp->selectGroup($group);
			$msgs = $nntp->getOverview($firstart."-".$lastart, true, false);
			if(PEAR::isError($msgs))
			{
				$nntp->doQuit();
				$nntp->doConnectNC();
				$nntp->selectGroup($group);
				$msgs = $nntp->getOverview($firstart."-".$lastart, true, false);
				if(PEAR::isError($msgs))
				{
					$nntp->doQuit();
					if ($this->echooutput)
						echo "Error downloading headers for script 'Sharing'.\nError follows: {$msgs->code}: {$msgs->message}\n";
					return $ret;
				}
			}

			// We got the messages, filter through the subjects. Download new articles.
			if (is_array($msgs) && count($msgs) > 0)
			{
				$current = false;
				$msgids = array();
				foreach ($msgs as $msg)
				{
					/* The pattern : nzb_guid - [1/1] "unixtime" (1/1) yEnc */
					// Filter through headers.
					if (preg_match('/^([a-f0-9]{40}) - \[\d+\/\d+\] "(\d+)" \(\d+\/\d+\) yEnc$/', $msg["Subject"], $matches))
					{
						if ($matches[2] < $settings["lastdate"])
							continue;
						else
						{
							if ($current === false)
							{
								if ($matches[1] == $settings["lasthash"])
									$current = true;
								continue;
							}
							// Download article body using message-id.
							else
							{
								$nntp->doConnect();
								$nntp->selectGroup($group);
								$body = $nntp->getMessage($group, $msg["Message-ID"]);
								$nntp->doQuit();
								// Continue if we don't receive the body.
								if ($body === false)
									continue;
								else
								{
									// Parse the body.
									if ($this->decodeBody === false)
										continue;
									else
										$ret++;
								}
							}
						}
					}
				}
			}
			// Nntp didnt return anything?
			else
			{
				$nntp->doQuit();
				continue;
			}

			// Done so break out
			if ($under === true || $firstart <= $first)
				break;
		}
		return $ret;
	}
}
?>
