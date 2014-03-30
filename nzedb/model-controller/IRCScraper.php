<?php

/**
 * Class IRCScraperRun
 */
class IRCScraper
{
	/**
	 * @var string
	 */
	protected $CurTitle;

	/**
	 * @var string
	 */
	protected $CurSize;

	/**
	 * @var string
	 */
	protected $CurCategory;

	/**
	 * @var string
	 */
	protected $CurPreDate;

	/**
	 * @var string
	 */
	protected $CurSource;

	/**
	 * @var string
	 */
	protected $CurMD5;

	/**
	 * @var string
	 */
	protected $CurReqID;

	/**
	 * @var string
	 */
	protected $CurGroupID;

	/**
	 * List of groups and their ID's
	 * @var array
	 */
	protected $groupList;

	/**
	 * @var Net_SmartIRC
	 */
	protected $IRC = null;

	/**
	 * Construct
	 */
	public function __construct()
	{
		$this->db = new DB();
		$this->groups = new Groups();

		$this->resetPreVariables();
		$this->groupList = array();
	}

	/**
	 * Destruct
	 */
	public function __destruct()
	{
		// Disconnect from IRC cleanly.
		if (!is_null($this->IRC) && is_resource($this->IRC)) {
			$this->IRC->disconnect();
		}
	}

	/**
	 * Main method for scraping.
	 *
	 * @param Net_SmartIRC $irc Instance of class Net_SmartIRC
	 * @param string $servertype efnet | corrupt
	 */
	public function startScraping(&$irc, $servertype)
	{
		$this->IRC = $irc;

		// Show debug in CLI.
		//$this->IRC->setDebug(SMARTIRC_DEBUG_ALL);

		// Use real sockets instead of fsock.
		$this->IRC->setUseSockets(true);

		switch($servertype) {
			case 'efnet':
				$server = SCRAPE_IRC_EFNET_SERVER;
				$port = SCRAPE_IRC_EFNET_PORT;
				$nickname = SCRAPE_IRC_EFNET_NICKNAME;
				$username = SCRAPE_IRC_EFNET_USERNAME;
				$realname = SCRAPE_IRC_EFNET_REALNAME;
				$password = SCRAPE_IRC_EFNET_PASSWORD;
				$channelList = array(
					// Channel                             Password.
					'#alt.binaries.inner-sanctum'          => null,
					'#alt.binaries.cd.image'               => null,
					'#alt.binaries.movies.divx'            => null,
					'#alt.binaries.sounds.mp3.complete_cd' => null,
					'#alt.binaries.warez'                  => null,
					'#alt.binaries.teevee'                 => 'teevee',
					'#alt.binaries.moovee'                 => 'moovee',
					'#alt.binaries.erotica'                => 'erotica',
					'#alt.binaries.flac'                   => 'flac',
					//'#alt.binaries.foreign'                => 'foreign'
				);
				$regex =
					// Simple regex, more advanced regex below when doing the real checks.
					'/' .
						'FILLED.*Pred.*ago' .                          // a.b.inner-sanctum
						'|' .
						'Thank.*you.*Req.*Id.*Request' .               // a.b.cd.image, a.b.movies.divx, a.b.sounds.mp3.complete_cd, a.b.warez
						'|' .
						'Thank.*?you.*?You.*are.*Filling.*Pred.*ago' . // a.b.flac a.b.teevee
						'|' .
						'Thank.*?You.*?Request.*?Filled!.*?ReqId' .    // a.b.moovee
						'|' .
						'That.*?was.*?awesome.*?Shall.*?ReqId' .       // a.b.erotica
					'/i';
				break;
			case 'corrupt':
				$server = SCRAPE_IRC_CORRUPT_SERVER;
				$port= SCRAPE_IRC_CORRUPT_PORT;
				$nickname = SCRAPE_IRC_CORRUPT_NICKNAME;
				$username = SCRAPE_IRC_CORRUPT_USERNAME;
				$realname = SCRAPE_IRC_CORRUPT_REALNAME;
				$password = SCRAPE_IRC_CORRUPT_PASSWORD;
				$channelList = array(
					'#pre' => null
				);
				$regex = '/PRE:.+?\[.+?\]/i'; // #pre
				break;
			default:
				return;
		}

		// This will scan channel messages for the regexes above.
		$this->IRC->registerActionhandler(SMARTIRC_TYPE_CHANNEL, $regex, $this, 'check_type');

		// Connect to IRC.
		$this->IRC->connect($server, $port);

		// Login to IRC.
		$this->IRC->login(
			// Nick name.
			$nickname,
			// Real name.
			$realname,
			// User mode.
			0,
			// User name.
			$username,
			// Password.
			($password === false ? null : $password)
		);

		// Join channels.
		$this->IRC->join($channelList);

		echo '[' . date('r') . '] [Scraping of IRC channels for ' . $servertype .' started.]' . PHP_EOL;

		// Wait for action handlers.
		$this->IRC->listen();

		// If we return from action handlers, disconnect from IRC.
		$this->IRC->disconnect();
	}

	/**
	 * Check channel and poster, send to right method.
	 *
	 * @param object $irc
	 * @param object $data
	 */
	public function check_type($irc, $data)
	{
		$channel = strtolower($data->channel);
		$poster  = strtolower($data->nick);

		switch ($poster) {
			case 'sanctum':
				if ($channel === '#alt.binaries.inner-sanctum') {
					$this->inner_sanctum($data->message);
				}
				break;

			case 'alt-bin':
				$this->alt_bin($data->message, $channel);
				break;

			case 'pr3':
				$this->corrupt_pre($data->message);
				break;

			case 'abflac':
				if ($channel === '#alt.binaries.flac') {
					$this->ab_flac($data->message);
				}
				break;

			case 'abking':
				if ($channel === '#alt.binaries.moovee') {
					$this->ab_moovee($data->message);
				}
				break;

			case 'ginger':
				if ($channel === '#alt.binaries.erotica') {
					$this->ab_erotica($data->message);
				}
				break;

			case 'abgod':
				if ($channel === '#alt.binaries.teevee') {
					$this->ab_teevee($data->message);
				}
				break;

			default:
				break;
		}
	}

	/**
	 * Get pre date from wD xH yM zS ago string.
	 *
	 * @param $agoString
	 */
	protected function getTimeFromAgo($agoString)
	{
		$predate = 0;
		// Get pre date from this format : 10m 54s
		if (preg_match('/((?P<day>\d+)d)?\s*((?P<hour>\d+)h)?\s*((?P<min>\d+)m)?\s*((?P<sec>\d+)s)?/i', $agoString, $matches)) {
			if (!empty($matches['day'])) {
				$predate += ((int)($matches['day']) * 86400);
			}
			if (!empty($matches['hour'])) {
				$predate += ((int)($matches['hour']) * 3600);
			}
			if (!empty($matches['min'])) {
				$predate += ((int)($matches['min']) * 60);
			}
			if (!empty($matches['sec'])) {
				$predate += (int)$matches['sec'];
			}
			if ($predate !== 0) {
				$predate = (time() - $predate);
			}
		}
		$this->CurPreDate = ($predate === 0 ? '' : $this->db->from_unixtime($predate));
	}

	/**
	 * Go through regex matches, find PRE info.
	 *
	 * @param array $matches
	 */
	protected function siftMatches(&$matches)
	{
		$this->CurMD5 = $this->db->escapeString(md5($matches['title']));
		$this->CurTitle = $matches['title'];

		if (isset($matches['reqid'])) {
			$this->CurReqID = $matches['reqid'];
		}
		if (isset($matches['size'])) {
			$this->CurSize = $matches['size'];
		}
		if (isset($matches['predago'])) {
			$this->getTimeFromAgo($matches['predago']);
		}
		if (isset($matches['categoru'])) {
			$this->CurCategory = $matches['category'];
		}
		$this->checkForDupe();
	}

	/**
	 * Gets new PRE from #a.b.erotica
	 *
	 * @param string $message The IRC message to parse.
	 */
	protected function ab_erotica(&$message)
	{
		//That was awesome [*Anonymous*] Shall we do it again? ReqId:[326264] [HD-Clip] [FULL 16x50MB TeenSexMovs.14.03.30.Daniela.XXX.720p.WMV-iaK] Filenames:[iak-teensexmovs-140330] Comments:[0] Watchers:[0] Total Size:[753MB] Points Earned:[54] [Pred 3m 20s ago]
		if (preg_match('/ReqId:\[(?P<reqid>\d+)\]\s+\[(?P<category>.+?)\]\s+\[FULL\s+\d+x\d+[KMGTP]?B\s+(?P<title>.+?)\].+?Size:\[(?P<size>.+?)\].+?\[Pred\s+(?P<pred>.+?)\s+ago\]/i', $message, $matches)) {
			$this->CurSource = '#a.b.erotica';
			$this->CurGroupID  = ($this->getGroupID('alt.binaries.erotica') === false ? '' : $this->getGroupID('alt.binaries.erotica'));
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.flac
	 *
	 * @param string $message The IRC message to parse.
	 */
	protected function ab_flac(&$message)
	{
		//Thank You [*Anonymous*] You are now Filling ReqId:[42548] [FULL VA-Diablo_III_Reaper_of_Souls_Collectors_Edition_Soundtrack-CD-FLAC-2014-BUDDHA] [Pred 55s ago]
		if (preg_match('/ReqID:.*?\[(?P<reqid>\d+)\]\s+\[FULL\s+(?P<title>.+?)\]\s+\[Pred\s+(?P<pred>.+?)\s+ago\]/i', $message, $matches)) {
			$this->CurSource = '#a.b.flac';
			$this->CurGroupID  = ($this->getGroupID('alt.binaries.flac') === false ? '' : $this->getGroupID('alt.binaries.flac'));
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.moovee
	 *
	 * @param string $message The IRC message to parse.
	 */
	protected function ab_moovee(&$message)
	{
		//Thank You [*Anonymous*] Request Filled! ReqId:[140445] [FULL 94x50MB Burning.Daylight.2010.720p.BluRay.x264-SADPANDA] Requested by:[*Anonymous* 3h 29m ago] Comments:[0] Watchers:[0] Points Earned:[314] [Pred 4h 29m ago]
		if (preg_match('/ReqId:\[(?P<reqid>\d+)\]\s+\[FULL\s+\d+x\d+[MGPTK]?B\s+(?P<title>.+?)\]\s+.+?\[Pred\s+(?P<pred>.+?)\s+ago\]/i', $message, $matches)) {
			$this->CurSource = '#a.b.moovee';
			$this->CurGroupID  = ($this->getGroupID('alt.binaries.moovee') === false ? '' : $this->getGroupID('alt.binaries.moovee'));
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.teevee
	 *
	 * @param string $message The IRC message to parse.
	 */
	protected function ab_teevee(&$message)
	{
		//Thank You [*Anonymous*] You are now Filling ReqId:[183443] [FULL Ant.and.Decs.Saturday.Night.Takeaway.S11E06.HDTV.x264-W4F] [Pred 1m 43s ago]
		if (preg_match('/ReqId:\[(?P<reqid>\d+)\]\s+\[FULL\s+(?P<title>.+?)\]\s+\[Pred\s+(?P<pred>.+?)\s+ago\]/', $message, $matches)) {
			$this->CurSource = '#a.b.teevee';
			$this->CurGroupID  = ($this->getGroupID('alt.binaries.teevee') === false ? '' : $this->getGroupID('alt.binaries.teevee'));
			$this->siftMatches($matches);
		}
	}
	/**
	 * Gets new PRE from #a.b.inner-sanctum.
	 *
	 * @param string $message The IRC message to parse.
	 */
	protected function corrupt_pre(&$message)
	{
		//PRE: [TV-X264] Tinga.Tinga.Fabeln.S02E11.Warum.Bienen.stechen.GERMAN.WS.720p.HDTV.x264-RFG
		if (preg_match('/^PRE: +\[(?P<category>.+?)\]\ +(?P<title>.+)$/i', $message, $matches)) {
			$this->CurSource   = '#pre@corrupt';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.inner-sanctum.
	 *
	 * @param string $message The IRC message to parse.
	 */
	protected function inner_sanctum(&$message)
	{	//[FILLED] [ 341953 | Emilie_Simon-Mue-CD-FR-2014-JUST | 16x79 | MP3 | *Anonymous* ] [ Pred 10m 54s ago ]
		//06[FILLED] [ 342184 06| DJ_Tuttle--Optoswitches_(FF_011)-VINYL-1997-CMC_INT 06| 7x47 06| MP3 06| *Anonymous* 06] 06[ Pred 5h 46m 10s ago 06]"
		if (preg_match('/FILLED.*?\]\s+\[\s+(?P<reqid>\d+)\s+.*?\|\s+(?P<title>.+?)\s+.*?\|\s+.+?\s+.*?\|\s+(?P<category>.+?)\s+.*?\|\s+.+?\s+.*?\]\s+.*?\[\s+Pred\s+(?P<pred>.+?)\s+ago\s+.*?\]/i', $message, $matches)) {
			$this->CurSource   = '#a.b.inner-sanctum';
			$this->CurGroupID  = ($this->getGroupID('alt.binaries.inner-sanctum') === false ? '' : $this->getGroupID('alt.binaries.inner-sanctum'));
			$this->siftMatches($matches);
		}
	}

	/**
	 * Get new PRE from Alt-Bin groups.
	 *
	 * @param string $message The IRC message from the bot.
	 * @param string $channel The IRC channel name.
	 */
	protected function alt_bin(&$message, &$channel)
	{
		//Thank you<Bijour> Req Id<137732> Request<The_Blueprint-Phenomenology-(Retail)-2004-KzT *Pars Included*> Files<19> Dates<Req:2014-03-24 Filling:2014-03-29> Points<Filled:1393 Score:25604>
		//Thank you<gizka> Req Id<42948> Request<Bloodsport.IV.1999.FS.DVDRip.XviD.iNT-EwDp *Pars Included*> Files<55> Dates<Req:2014-03-22 Filling:2014-03-29> Points<Filled:93 Score:5607>
		if (preg_match('/Req.+?Id.*?<.*?(?P<reqid>\d+).*?>.*?Request.*?<\d{0,2}(?P<title>.+?)(\s+\*Pars\s+Included\*\d{0,2}>|\d{0,2}>)\s+/i', $message, $matches)) {
			$this->CurGroupID  = ($this->getGroupID(str_replace('#', '', $channel)) === false ? '' : $this->getGroupID(str_replace('#', '', $channel)));
			$this->CurSource = str_replace('#alt.binaries', '#a.b', $channel);
			$this->siftMatches($matches);
		}
	}

	/**
	 * Check if we already have the PRE.
	 *
	 * @return bool True if we already have, false if we don't.
	 */
	protected function checkForDupe()
	{
		if ($this->db->queryOneRow(sprintf('SELECT id FROM predb WHERE md5 = %s', $this->CurMD5)) === false) {
			$this->insertNewPre();
		} else {
			$this->updatePre();
		}
	}

	/**
	 * Insert new PRE into the DB.
	 */
	protected function insertNewPre()
	{
		if (empty($this->CurTitle)) {
			return;
		}

		$query = 'INSERT INTO predb (';

		$query .= (!empty($this->CurSize)     ? 'size, '      : '');
		$query .= (!empty($this->CurCategory) ? 'category, '  : '');
		$query .= (!empty($this->CurSource)   ? 'source, '    : '');
		$query .= (!empty($this->CurReqID)    ? 'requestid, ' : '');
		$query .= (!empty($this->CurGroupID)  ? 'groupid, '   : '');

		$query .= 'predate, md5, title, adddate) VALUES (';

		$query .= (!empty($this->CurSize)     ? $this->db->escapeString($this->CurSize)     . ', '   : '');
		$query .= (!empty($this->CurCategory) ? $this->db->escapeString($this->CurCategory) . ', '   : '');
		$query .= (!empty($this->CurSource)   ? $this->db->escapeString($this->CurSource)   . ', '   : '');
		$query .= (!empty($this->CurReqID)    ? $this->CurReqID                             . ', '   : '');
		$query .= (!empty($this->CurGroupID)  ? $this->CurGroupID                           . ', '   : '');
		$query .= (!empty($this->CurPreDate)  ? $this->CurPreDate                           . ', '   : 'NOW(), ');

		$query .= '%s, %s, NOW())';

		$this->db->queryExec(
			sprintf(
				$query,
				$this->CurMD5,
				$this->db->escapeString($this->CurTitle)
			)
		);

		echo '[' . date('r') . '] [New PRE] [' . $this->CurSource . '] [' . $this->CurTitle . ']' . PHP_EOL;

		$this->resetPreVariables();
	}

	/**
	 * Updates PRE data in the DB.
	 */
	protected function updatePre()
	{
		if (empty($this->CurTitle)) {
			return;
		}

		$query = 'UPDATE predb SET ';

		$query .= (!empty($this->CurSize)     ? 'size = '      . $this->db->escapeString($this->CurSize)     . ', ' : '');
		$query .= (!empty($this->CurCategory) ? 'category = '  . $this->db->escapeString($this->CurCategory) . ', ' : '');
		$query .= (!empty($this->CurSource)   ? 'source = '    . $this->db->escapeString($this->CurSource)   . ', ' : '');
		$query .= (!empty($this->CurReqID)    ? 'requestid = ' . $this->CurReqID                             . ', ' : '');
		$query .= (!empty($this->CurGroupID)  ? 'groupid = '   . $this->CurGroupID                           . ', ' : '');
		$query .= (!empty($this->CurPreDate)  ? 'predate = '   . $this->CurPreDate                           . ', ' : '');

		if ($query === 'UPDATE predb SET '){
			return;
		}

		$query .= 'title = ' . $this->db->escapeString($this->CurTitle);
		$query .= ' WHERE md5 = ' . $this->CurMD5;

		$this->db->queryExec($query);

		echo '[' . date('r') . '] [Updated PRE] [' . $this->CurSource . '] [' . $this->CurTitle . ']' . PHP_EOL;

		$this->resetPreVariables();
	}

	/**
	 * Get a group ID for a group name.
	 *
	 * @param string $groupName
	 *
	 * @return mixed
	 */
	protected function getGroupID($groupName)
	{
		if (!isset($this->groupList[$groupName])) {
			$this->groupList[$groupName] = $this->groups->getIDByName($groupName);
		}
		return $this->groupList[$groupName];
	}

	/**
	 * After updating or inserting new PRE, reset these.
	 */
	protected function resetPreVariables()
	{
		$this->CurTitle    = '';
		$this->CurSize     = '';
		$this->CurCategory = '';
		$this->CurPreDate  = '';
		$this->CurSource   = '';
		$this->CurMD5      = '';
		$this->CurReqID    = '';
		$this->CurGroupID  = '';
	}
}