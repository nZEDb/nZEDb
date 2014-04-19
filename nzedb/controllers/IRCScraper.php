<?php
use nzedb\db\DB;

/**
 * Class IRCScraper
 */
class IRCScraper extends IRCClient
{
	/**
	 * Array of current pre info.
	 * @var array
	 * @access protected
	 */
	protected $CurPre;

	/**
	 * Array of old pre info.
	 * @var array
	 * @access protected
	 */
	protected $OldPre;

	/**
	 * List of groups and their ID's
	 * @var array
	 * @access protected
	 */
	protected $groupList;

	/**
	 * @var DB
	 * @access protected
	 */
	protected $db;

	/**
	 * Current server.
	 * efnet | corrupt | zenet
	 * @var string
	 * @access protected
	 */
	protected $serverType;

	/**
	 * Run this in silent mode (no text output).
	 * @var bool
	 * @access protected
	 */
	protected $silent;

	/**
	 * Is this pre nuked or un nuked?
	 * @var bool
	 * @access protected
	 */
	protected $nuked;

	/**
	 * Construct
	 *
	 * @param string       $serverType   efnet | corrupt | zenet
	 * @param bool         $silent       Run this in silent mode (no text output).
	 * @param bool         $debug        Turn on Net_SmartIRC debug?
	 *
	 * @access public
	 */
	public function __construct($serverType, &$silent = false, &$debug = false)
	{
		$this->db = new DB();
		$this->groupList = array();
		$this->serverType = $serverType;
		$this->silent = $silent;
		$this->_debug = $debug;
		$this->resetPreVariables();
		$this->startScraping();
	}

	/**
	 * Main method for scraping.
	 *
	 * @access protected
	 */
	protected function startScraping()
	{
		switch($this->serverType) {
			case 'efnet':
				$server   = SCRAPE_IRC_EFNET_SERVER;
				$port     = SCRAPE_IRC_EFNET_PORT;
				$nickname = SCRAPE_IRC_EFNET_NICKNAME;
				$username = SCRAPE_IRC_EFNET_USERNAME;
				$realname = SCRAPE_IRC_EFNET_REALNAME;
				$password = SCRAPE_IRC_EFNET_PASSWORD;
				$tls      = SCRAPE_IRC_EFNET_ENCRYPTION;
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
					'#alt.binaries.foreign'                => 'foreign',
					'#alt.binaries.console.ps3'            => null,
					'#alt.binaries.games.nintendods'       => null,
					'#alt.binaries.games.wii'              => null,
					'#alt.binaries.games.xbox360'          => null,
					'#alt.binaries.sony.psp'               => null,
					'#scnzb'                               => null,
					//'#tvnzb'                               => null
				);
				// Check if the user is ignoring channels.
				if (defined('SCRAPE_IRC_EFNET_IGNORED_CHANNELS') && SCRAPE_IRC_EFNET_IGNORED_CHANNELS != '') {
					$ignored = explode(',', SCRAPE_IRC_EFNET_IGNORED_CHANNELS);
					$newList = array();
					foreach($channelList as $channel => $chanpass) {
						if (!in_array($channel, $ignored)) {
							$newList[$channel] = $chanpass;
						}
					}
					if (empty($newList)) {
						exit('ERROR: You have ignored every group there is to scrape!' . PHP_EOL);
					}
					$channelList = $newList;
					unset($newList);
				}
				break;

			case 'corrupt':
				$server      = SCRAPE_IRC_CORRUPT_SERVER;
				$port        = SCRAPE_IRC_CORRUPT_PORT;
				$nickname    = SCRAPE_IRC_CORRUPT_NICKNAME;
				$username    = SCRAPE_IRC_CORRUPT_USERNAME;
				$realname    = SCRAPE_IRC_CORRUPT_REALNAME;
				$password    = SCRAPE_IRC_CORRUPT_PASSWORD;
				$tls         = SCRAPE_IRC_CORRUPT_ENCRYPTION;
				$channelList = array('#pre' => null);
				break;

			case 'zenet':
				$server      = SCRAPE_IRC_ZENET_SERVER;
				$port        = SCRAPE_IRC_ZENET_PORT;
				$nickname    = SCRAPE_IRC_ZENET_NICKNAME;
				$username    = SCRAPE_IRC_ZENET_USERNAME;
				$realname    = SCRAPE_IRC_ZENET_REALNAME;
				$password    = SCRAPE_IRC_ZENET_PASSWORD;
				$tls         = SCRAPE_IRC_ZENET_ENCRYPTION;
				$channelList = array('#Pre' => null);
				break;

			default:
				return;
		}

		// Connect to IRC.
		if ($this->connect($server, $port, $tls) === false) {
			exit (
				'Error connecting to (' .
				$server .
				':' .
				$port .
				'). Please verify your server information and try again.' .
				PHP_EOL
			);
		}

		// Login to IRC.
		if ($this->login($nickname, $username, $realname, $password) === false) {
			exit('Error logging in to: (' .
				$server . ':' . $port . ') nickname: (' . $nickname .
				'). Verify your connection information, you might also be banned from this server or there might have been a connection issue.' .
				PHP_EOL
			);
		}

		// Join channels.
		$this->joinChannels($channelList);

		if (!$this->silent) {
			echo
				'[' .
				date('r') .
				'] [Scraping of IRC channels for (' .
				$server .
				':' .
				$port .
				') (' .
				$nickname .
				') started.]' .
				PHP_EOL;
		}

		// Scan incoming IRC messages.
		$this->readIncoming();
	}

	/**
	 * Check the similarity between 2 words.
	 *
	 * @param string $word1
	 * @param string $word2
	 * @param int    $similarity
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function checkSimilarity(&$word1, $word2, $similarity = 49)
	{
		similar_text($word1, $word2, $percent);
		if ($percent > $similarity) {
			return true;
		}
		return false;
	}

	/**
	 * Check channel and poster, send message to right method.
	 * access protected
	 */
	protected function processChannelMessages()
	{
		$channel = strtolower($this->_channelData['channel']);
		$poster  = strtolower($this->_channelData['nickname']);

		switch($channel) {

			case '#alt.binaries.inner-sanctum':
				if ($this->checkSimilarity($poster, 'sanctum')) {
					$this->inner_sanctum();
				}
				break;

			case '#alt.binaries.erotica':
				if ($this->checkSimilarity($poster, 'ginger') || $this->checkSimilarity($poster, 'g1nger')) {
					$this->ab_erotica();
				}
				break;

			case '#alt.binaries.flac':
				if ($this->checkSimilarity($poster, 'abflac')) {
					$this->ab_flac();
				}
				break;

			case '#alt.binaries.moovee':
				if ($this->checkSimilarity($poster, 'abking')) {
					$this->ab_moovee();
				}
				break;

			case '#alt.binaries.teevee':
				if ($this->checkSimilarity($poster, 'abgod')) {
					$this->ab_teevee();
				}
				break;

			case '#pre':
				if ($this->checkSimilarity($poster, 'theannouncer')) {
					$this->zenet_pre();
				} else if ($this->checkSimilarity($poster, 'pr3')) {
					$this->corrupt_pre();
				}
				break;

			case '#alt.binaries.foreign':
				if ($this->checkSimilarity($poster, 'abqueen')) {
					$this->ab_foreign();
				}
				break;

			case '#alt.binaries.console.ps3':
				if ($this->checkSimilarity($poster, 'binarybot')) {
					$this->ab_console_ps3();
				}
				break;

			case '#alt.binaries.games.nintendods':
				if ($this->checkSimilarity($poster, 'binarybot')) {
					$this->ab_games_nintendods();
				}
				break;

			case '#alt.binaries.games.wii':
				if ($this->checkSimilarity($poster, 'binarybot') || $this->checkSimilarity($poster, 'googlebot')) {
					$this->ab_games_wii($poster);
				}
				break;

			case '#alt.binaries.games.xbox360':
				if ($this->checkSimilarity($poster, 'binarybot') || $this->checkSimilarity($poster, 'googlebot')) {
					$this->ab_games_xbox360($poster);
				}
				break;

			case '#alt.binaries.sony.psp':
				if ($this->checkSimilarity($poster, 'googlebot')) {
					$this->ab_sony_psp();
				}
				break;

			case '#scnzb':
				if ($this->checkSimilarity($poster, 'nzbs')) {
					$this->scnzb();
				}
				break;

			/*case '#tvnzb':
				if ($this->checkSimilarity($poster, 'tweetie')) {
					$this->tvnzb();
				}
				break;*/

			default:
				if ($this->checkSimilarity($poster, 'alt-bin')) {
					$this->alt_bin($channel);
				}
		}
	}

	/**
	 * Get pre date from wD xH yM zS ago string.
	 *
	 * @param $agoString
	 *
	 * @access protected
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
		$this->CurPre['predate'] = ($predate === 0 ? '' : $this->db->from_unixtime($predate));
	}

	/**
	 * Go through regex matches, find PRE info.
	 *
	 * @param array $matches
	 *
	 * @access protected
	 */
	protected function siftMatches(&$matches)
	{
		$this->CurPre['md5'] = $this->db->escapeString(md5($matches['title']));
		$this->CurPre['sha1'] = $this->db->escapeString(sha1($matches['title']));
		$this->CurPre['title'] = $matches['title'];

		if (isset($matches['reqid'])) {
			$this->CurPre['reqid'] = $matches['reqid'];
		}
		if (isset($matches['size'])) {
			$this->CurPre['size'] = $matches['size'];
		}
		if (isset($matches['predago'])) {
			$this->getTimeFromAgo($matches['predago']);
		}
		if (isset($matches['category'])) {
			$this->CurPre['category'] = $matches['category'];
		}
		if (isset($matches['nuke'])) {
			$this->nuked = true;
			switch ($matches['nuke']) {
				case 'NUKE':
					$this->CurPre['nuked'] = PreDb::PRE_NUKED;
					break;
				case 'UNNUKE':
					$this->CurPre['nuked'] = PreDb::PRE_UNNUKED;
					break;
				case 'MODNUKE':
					$this->CurPre['nuked'] = PreDb::PRE_MODNUKE;
					break;
				case 'RENUKE':
					$this->CurPre['nuked'] = PreDb::PRE_RENUKED;
					break;
				case 'OLDNUKE':
					$this->CurPre['nuked'] = PreDb::PRE_OLDNUKE;
					break;
			}
		}
		if (isset($matches['reason'])) {
			$this->CurPre['reason'] = substr($matches['reason'], 0, 255);
		}
		if (isset($matches['files'])) {
			$this->CurPre['files'] = substr($matches['files'], 0, 50);

			// If the pre has no size, try to get one from files.
			if (empty($this->OldPre['size']) && empty($this->CurPre['size'])) {
				if (preg_match('/(?P<files>\d+)x(?P<size>\d+)\s*(?P<ext>[KMGTP]?B)\s*$/i', $matches['files'], $match)) {
					$this->CurPre['size'] = ((int)$match['files'] * (int)$match['size']) . $match['ext'];
					unset($match);
				}
			}
		}
		$this->checkForDupe();
	}

	/**
	 * Gets new PRE from #a.b.erotica
	 *
	 * @access protected
	 */
	protected function ab_erotica()
	{
		//That was awesome [*Anonymous*] Shall we do it again? ReqId:[326264] [HD-Clip] [FULL 16x50MB TeenSexMovs.14.03.30.Daniela.XXX.720p.WMV-iaK] Filenames:[iak-teensexmovs-140330] Comments:[0] Watchers:[0] Total Size:[753MB] Points Earned:[54] [Pred 3m 20s ago]
		//That was awesome [*Anonymous*] Shall we do it again? ReqId:[326663] [x264] [FULL 53x100MB Young.Ripe.Mellons.10.XXX.720P.WEBRIP.X264-GUSH] Filenames:[gush.yrmellons10] Comments:[1] Watchers:[0] Total Size:[4974MB] Points Earned:[354] [Pred 7m 5s ago] [NUKED]
		if (preg_match('/ReqId:\[(?P<reqid>\d+)\]\s+\[.+?\]\s+\[FULL\s+(?P<files>\d+x\d+[KMGTP]?B)\s+(?P<title>.+?)\].+?Size:\[(?P<size>.+?)\](.+?\[Pred\s+(?P<predago>.+?)\s+ago\])?(.+?\[(?P<nuke>(MOD|OLD|RE|UN)?NUKE)D\])?/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = '#a.b.erotica';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.erotica');
			$this->CurPre['category'] = 'XXX';
			$this->siftMatches($matches);

		//[NUKE] ReqId:[326663] [Young.Ripe.Mellons.10.XXX.720P.WEBRIP.X264-GUSH] Reason:[selfdupe.2014-03-09]
		} elseif (preg_match('/\[(?P<nuke>(MOD|OLD|RE|UN)?NUKE)\]\s+ReqId:\[(?P<reqid>\d+)\]\s+\[(?P<title>.+?)\]\s+Reason:\[(?P<reason>.+?)]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = '#a.b.erotica';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.erotica');
			$this->CurPre['category'] = 'XXX';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.flac
	 *
	 * @access protected
	 */
	protected function ab_flac()
	{
		//Thank You [*Anonymous*] Request Filled! ReqId:[42614] [FULL 10x15MB You_Blew_It-Keep_Doing_What_Youre_Doing-CD-FLAC-2014-WRE] Requested by:[*Anonymous* 21s ago] Comments:[0] Watchers:[0] Points Earned:[10] [Pred 3m 16s ago]
		if (preg_match('/Request\s+Filled!\s+ReqId:\[(?P<reqid>\d+)\]\s+\[FULL\s+(?P<files>\d+x\d+[KMGTP]?B)\s+(?P<title>.+?)\].+?\[Pred\s+(?P<predago>.+?)\s+ago\]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = '#a.b.flac';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.sounds.flac');
			$this->CurPre['category'] = 'FLAC';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.moovee
	 *
	 * @access protected
	 */
	protected function ab_moovee()
	{
		//Thank You [*Anonymous*] Request Filled! ReqId:[140445] [FULL 94x50MB Burning.Daylight.2010.720p.BluRay.x264-SADPANDA] Requested by:[*Anonymous* 3h 29m ago] Comments:[0] Watchers:[0] Points Earned:[314] [Pred 4h 29m ago]
		if (preg_match('/ReqId:\[(?P<reqid>\d+)\]\s+\[FULL\s+(?P<files>\d+x\d+[MGPTK]?B)\s+(?P<title>.+?)\]\s+.+?\[Pred\s+(?P<predago>.+?)\s+ago\]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = '#a.b.moovee';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.moovee');
			$this->CurPre['category'] = 'Movies';
			$this->siftMatches($matches);

		//[NUKE] ReqId:[130274] [NOVA.The.Bibles.Buried.Secrets.2008.DVDRip.XviD-FiCO] Reason:[field.shifted_oi47.tinypic.com.24evziv.jpg]
		} else if (preg_match('/\[(?P<nuke>(MOD|OLD|RE|UN)?NUKE)\]\s+ReqId:\[(?P<reqid>\d+)\]\s+\[(?P<title>.+?)\]\s+Reason:\[(?P<reason>.+?)\]/', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = '#a.b.moovee';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.moovee');
			$this->CurPre['category'] = 'Movies';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.foreign
	 *
	 * @access protected
	 */
	protected function ab_foreign()
	{
		//Thank You [*Anonymous*] Request Filled! ReqId:[61525] [Movie] [FULL 95x50MB Wadjda.2012.PAL.MULTI.DVDR-VIAZAC] Requested by:[*Anonymous* 5m 13s ago] Comments:[0] Watchers:[0] Points Earned:[317] [Pred 8m 27s ago]
		if (preg_match('/ReqId:\[(?P<reqid>\d+)\]\s+\[(?P<category>.+?)\]\s+\[FULL\s+(?P<files>\d+x\d+[MGPTK]?B)\s+(?P<title>.+?)\]\s+.+?\[Pred\s+(?P<predago>.+?)\s+ago\]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']  = '#a.b.foreign';
			$this->CurPre['groupid'] = $this->getGroupID('alt.binaries.mom');
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.teevee
	 *
	 * @access protected
	 */
	protected function ab_teevee()
	{
		//Thank You [*Anonymous*] Request Filled! ReqId:[183520] [FULL 19x50MB Louis.Therouxs.LA.Stories.S01E02.720p.HDTV.x264-FTP] Requested by:[*Anonymous* 53s ago] Comments:[0] Watchers:[0] Points Earned:[64] [Pred 3m 45s ago]
		if (preg_match('/Request\s+Filled!\s+ReqId:\[(?P<reqid>\d+)\]\s+\[FULL\s+(?P<files>\d+x\d+[KMGPT]?B)\s+(?P<title>.+?)\].+?\[Pred\s+(?P<predago>.+?)\s+ago\]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = '#a.b.teevee';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.teevee');
			$this->CurPre['category'] = 'TV';
			$this->siftMatches($matches);

		//[NUKE] ReqId:[183497] [From.Dusk.Till.Dawn.S01E01.720p.HDTV.x264-BATV] Reason:[bad.ivtc.causing.jerky.playback.due.to.dupe.and.missing.frames.in.segment.from.16m.to.30m]
		//[UNNUKE] ReqId:[183449] [The.Biggest.Loser.AU.S09E29.PDTV.x264-RTA] Reason:[get.samplefix]
		} else if (preg_match('/\[(?P<nuke>(MOD|OLD|RE|UN)?NUKE)\]\s+ReqId:\[(?P<reqid>\d+)\]\s+\[(?P<title>.+?)\]\s+Reason:\[(?P<reason>.+?)\]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = '#a.b.teevee';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.teevee');
			$this->CurPre['category'] = 'TV';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.console.ps3
	 *
	 * @access protected
	 */
	protected function ab_console_ps3()
	{
		//[Anonymous person filling request for: FULL 56 Ragnarok.Odyssey.ACE.PS3-iMARS NTSC BLURAY imars-ragodyace-ps3 56x100MB by Khaine13 on 2014-03-29 13:14:12][ReqID: 4888][You get a bonus of 6 for a total points earning of: 62 for filling with 10% par2s!][Your score will be adjusted once you have -filled 4888]
		if (preg_match('/\s+FULL\s+\d+\s+(?P<title>.+?)\s+(?P<files>\d+x\d+[KMGTP]?B)\s+.+?\]\[ReqID:\s+(?P<reqid>\d+)\]\[/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = '#a.b.console.ps3';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.console.ps3');
			$this->CurPre['category'] = 'PS3';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.games.wii
	 *
	 * @param string $poster  The name of the poster.
	 *
	 * @access protected
	 */
	protected function ab_games_wii(&$poster)
	{
		//A new NZB has been added: Go_Diego_Go_Great_Dinosaur_Rescue_PAL_WII-ZER0 PAL DVD5 zer0-gdggdr 93x50MB - To download this file: -sendnzb 12811
		if ($this->checkSimilarity($poster, 'googlebot') && preg_match('/A\s+new\s+NZB\s+has\s+been\s+added:\s+(?P<title>.+?)\s+.+?(?P<files>\d+x\d+[KMGTP]?B)\s+-\s+To.+?file:\s+-sendnzb\s+(?P<reqid>\d+)\s*/i', $this->_channelData['message'], $matches)) {
			$matches['nuke']          = 'NUKE';
			$this->CurPre['source']   = '#a.b.games.wii';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.games.wii');
			$this->CurPre['category'] = 'WII';
			$this->siftMatches($matches);

		//[kiczek added reason info for: Samurai_Shodown_IV_-_Amakusas_Revenge_USA_VC_NEOGEO_Wii-OneUp][VCID: 5027][Value: bad.dirname_bad.filenames_get.repack]
		} else if ($this->checkSimilarity($poster, 'binarybot') && preg_match('/added\s+(nuke|reason)\s+info\s+for:\s+(?P<title>.+?)\]\[VCID:\s+(?P<reqid>\d+)\]\[Value:\s+(?P<reason>.+?)\]/i', $this->_channelData['message'], $matches)) {
			$matches['nuke']          = 'NUKE';
			$this->CurPre['source']   = '#a.b.games.wii';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.games.wii');
			$this->CurPre['category'] = 'WII';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.games.xbox360
	 *
	 * @param string $poster  The name of the poster.
	 *
	 * @access protected
	 */
	protected function ab_games_xbox360(&$poster)
	{
		//A new NZB has been added: South.Park.The.Stick.of.Truth.PAL.XBOX360-COMPLEX PAL DVD9 complex-south.park.sot 74x100MB - To download this file: -sendnzb 19909
		if ($this->checkSimilarity($poster, 'googlebot') && preg_match('/A\s+new\s+NZB\s+has\s+been\s+added:\s+(?P<title>.+?)\s+.+?(?P<files>\d+x\d+[KMGTP]?B)\s+-\s+To.+?file:\s+-sendnzb\s+(?P<reqid>\d+)\s*/i', $this->_channelData['message'], $matches)) {
			$matches['nuke']          = 'NUKE';
			$this->CurPre['source']   = '#a.b.games.xbox360';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.games.xbox360');
			$this->CurPre['category'] = 'XBOX360';
			$this->siftMatches($matches);

		//[egres added nuke info for: Injustice.Gods.Among.Us.XBOX360-SWAG][GameID: 7088][Value: Y]
		} else if ($this->checkSimilarity($poster, 'binarybot') && preg_match('/added\s+(nuke|reason)\s+info\s+for:\s+(?P<title>.+?)\]\[VCID:\s+(?P<reqid>\d+)\]\[Value:\s+(?P<reason>.+?)\]/i', $this->_channelData['message'], $matches)) {
			$matches['nuke']          = 'NUKE';
			$this->CurPre['source']   = '#a.b.games.xbox360';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.games.xbox360');
			$this->CurPre['category'] = 'XBOX360';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.sony.psp
	 *
	 * @access protected
	 */
	protected function ab_sony_psp()
	{
		//A NZB is available: Satomi_Hakkenden_Hachitama_no_Ki_JPN_PSP-MOEMOE JAP UMD moe-satomi 69x20MB - To download this file: -sendnzb 21924
		if (preg_match('/A NZB is available:\s(?P<title>.+?)\s+.+?(?P<files>\d+x\d+[KMGPT]?B)\s+-.+?file:\s+-sendnzb\s+(?P<reqid>\d+)\s*/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = '#a.b.sony.psp';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.sony.psp');
			$this->CurPre['category'] = 'PSP';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.games_nintendods
	 *
	 * @access protected
	 */
	protected function ab_games_nintendods()
	{
		//NEW [NDS] PRE: Honda_ATV_Fever_USA_NDS-EXiMiUS
		if (preg_match('/NEW\s+\[NDS\]\s+PRE:\s+(?P<title>.+)/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = '#a.b.games.nintendods';
			$this->CurPre['groupid']  = $this->getGroupID('alt.binaries.games.nintendods');
			$this->CurPre['category'] = 'NDS';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #scnzb (boneless)
	 *
	 * @access protected
	 */
	protected function scnzb()
	{
		//[Complete][512754] Formula1.2014.Malaysian.Grand.Prix.Team.Principals.Press.Conference.720p.HDTV.x264-W4F  NZB: http://scnzb.eu/1pgOmwj
		if (preg_match('/\[Complete\]\[(?P<reqid>\d+)\]\s*(?P<title>.+?)\s+NZB:/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']  = '#scnzb';
			$this->CurPre['groupid'] = $this->getGroupID('alt.binaries.boneless');
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #tvnzb (sickbeard)
	 *
	 * @access protected
	 */
/*	protected function tvnzb()
	{
		//[SBINDEX] Rev.S03E02.HDTV.x264-TLA :: TV > HD :: 210.13 MB :: Aired: 31/Mar/2014 :: http://lolo.sickbeard.com/getnzb/aa10bcef235c604612dd61b0627ae25f.nzb
		if (preg_match('/\[SBINDEX\]\s+(?P<title>.+?)\s+::\s+(?P<sbcat>.+?)\s+::\s+(?P<size>.+?)\s+::\s+Aired/i', $this->_channelData['message'], $matches)) {
			if (preg_match('/^(?P<first>.+?)\s+>\s+(?P<last>.+?)$/', $matches['sbcat'], $match)) {
				$matches['category'] = $match['first'] . '-' . $match['last'];
			}
			$this->CurPre['source'] = '#tvnzb';
			$this->siftMatches($matches);
		}
	}*/

	/**
	 * Gets new PRE from #Pre on zenet
	 *
	 * @access protected
	 */
	protected function zenet_pre()
	{
		//(PRE) (XXX) (The.Golden.Age.Of.Porn.Candy.Samples.XXX.WEBRIP.WMV-GUSH)
		if (preg_match('/^\(PRE\)\s+\((?P<category>.+?)\)\s+\((?P<title>.+?)\)$/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source'] = '#Pre@zenet';
			$this->siftMatches($matches);

		//(NUKE) (German_TOP100_Single_Charts_31_03_2014-MCG) (selfmade.compilations.not.allowed)
		//(UNNUKE) (The.Biggest.Loser.AU.S09E29.PDTV.x264-RTA) (get.samplefix)
		} else if (preg_match('/\((?P<nuke>(MOD|OLD|RE|UN)?NUKE)\)\s+\((?P<title>.+?)\)\s+\((?P<reason>.+?)\)/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source'] = '#Pre@zenet';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #pre on Corrupt-net
	 *
	 * @access protected
	 */
	protected function corrupt_pre()
	{
		//PRE: [TV-X264] Tinga.Tinga.Fabeln.S02E11.Warum.Bienen.stechen.GERMAN.WS.720p.HDTV.x264-RFG
		if (preg_match('/^PRE:\s+\[(?P<category>.+?)\]\s+(?P<title>.+)$/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source'] = '#pre@corrupt';
			$this->siftMatches($matches);

		//NUKE: Miclini-Sunday_Morning_P1-DIRFIX-DAB-03-30-2014-G4E [dirfix.must.state.name.of.release.being.fixed] [EthNet]
		//UNNUKE: Youssoupha-Sur_Les_Chemins_De_Retour-FR-CD-FLAC-2009-0MNi [flac.rule.4.12.states.ENGLISH.artist.and.title.must.be.correct.and.this.is.not.ENGLISH] [LocalNet]
		//MODNUKE: Miclini-Sunday_Morning_P1-DIRFIX-DAB-03-30-2014-G4E [nfo.must.state.name.of.release.being.fixed] [EthNet]
		} else if (preg_match('/(?P<nuke>(MOD|OLD|RE|UN)?NUKE):\s+(?P<title>.+?)\s+\[(?P<reason>.+?)\]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source'] = '#pre@corrupt';
			$this->siftMatches($matches);
		}
	}

	/**
	 * Gets new PRE from #a.b.inner-sanctum.
	 *
	 * @access protected
	 */
	protected function inner_sanctum()
	{
		//[FILLED] [ 341953 | Emilie_Simon-Mue-CD-FR-2014-JUST | 16x79 | MP3 | *Anonymous* ] [ Pred 10m 54s ago ]
		if (preg_match('/FILLED\]\s+\[\s+(?P<reqid>\d+)\s+\|\s+(?P<title>.+?)\s+\|\s+(?P<files>\d+x\d+)\s+\|\s+(?P<category>.+?)\s+\|\s+.+?\s+\]\s+\[\s+Pred\s+(?P<predago>.+?)\s+ago\s+\]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']  = '#a.b.inner-sanctum';
			$this->CurPre['groupid'] = $this->getGroupID('alt.binaries.inner-sanctum');
			$this->siftMatches($matches);
		}
	}

	/**
	 * Get new PRE from Alt-Bin groups.
	 *
	 * @param string $channel The IRC channel name.
	 *
	 * @access protected
	 */
	protected function alt_bin(&$channel)
	{
		//Thank you<Bijour> Req Id<137732> Request<The_Blueprint-Phenomenology-(Retail)-2004-KzT *Pars Included*> Files<19> Dates<Req:2014-03-24 Filling:2014-03-29> Points<Filled:1393 Score:25604>
		if (preg_match('/Req.+?Id.*?<.*?(?P<reqid>\d+).*?>.*?Request.*?<\d{0,2}(?P<title>.+?)(\s+\*Pars\s+Included\*\d{0,2}>|\d{0,2}>)\s+Files<(?P<files>\d+)>/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']  = str_replace('#alt.binaries', '#a.b', $channel);
			$this->CurPre['groupid'] = $this->getGroupID(str_replace('#', '', $channel));
			$this->siftMatches($matches);
		}
	}

	/**
	 * Check if we already have the PRE.
	 *
	 * @return bool True if we already have, false if we don't.
	 *
	 * @access protected
	 */
	protected function checkForDupe()
	{
		$this->OldPre = $this->db->queryOneRow(sprintf('SELECT category, size FROM predb WHERE md5 = %s', $this->CurPre['md5']));
		if ($this->OldPre === false) {
			$this->insertNewPre();
		} else {
			$this->updatePre();
		}
	}

	/**
	 * Insert new PRE into the DB.
	 *
	 * @access protected
	 */
	protected function insertNewPre()
	{
		if (empty($this->CurPre['title'])) {
			return;
		}

		$query = 'INSERT INTO predb (';

		$query .= (!empty($this->CurPre['size'])     ? 'size, '       : '');
		$query .= (!empty($this->CurPre['category']) ? 'category, '   : '');
		$query .= (!empty($this->CurPre['source'])   ? 'source, '     : '');
		$query .= (!empty($this->CurPre['reason'])   ? 'nukereason, ' : '');
		$query .= (!empty($this->CurPre['files'])    ? 'files, '      : '');
		$query .= (!empty($this->CurPre['reqid'])    ? 'requestid, '  : '');
		$query .= (!empty($this->CurPre['groupid'])  ? 'groupid, '    : '');
		$query .= (!empty($this->CurPre['nuked'])    ? 'nuked, '      : '');

		$query .= 'predate, md5, sha1, title) VALUES (';

		$query .= (!empty($this->CurPre['size'])     ? $this->db->escapeString($this->CurPre['size'])     . ', '   : '');
		$query .= (!empty($this->CurPre['category']) ? $this->db->escapeString($this->CurPre['category']) . ', '   : '');
		$query .= (!empty($this->CurPre['source'])   ? $this->db->escapeString($this->CurPre['source'])   . ', '   : '');
		$query .= (!empty($this->CurPre['reason'])   ? $this->db->escapeString($this->CurPre['reason'])   . ', '   : '');
		$query .= (!empty($this->CurPre['files'])    ? $this->db->escapeString($this->CurPre['files'])    . ', '   : '');
		$query .= (!empty($this->CurPre['reqid'])    ? $this->CurPre['reqid']                             . ', '   : '');
		$query .= (!empty($this->CurPre['groupid'])  ? $this->CurPre['groupid']                           . ', '   : '');
		$query .= (!empty($this->CurPre['nuked'])    ? $this->CurPre['nuked']                             . ', '   : '');
		$query .= (!empty($this->CurPre['predate'])  ? $this->CurPre['predate']                           . ', '   : 'NOW(), ');

		$query .= '%s, %s, %s)';

		$this->db->ping(true);

		$this->db->queryExec(
			sprintf(
				$query,
				$this->CurPre['md5'],
				$this->CurPre['sha1'],
				$this->db->escapeString($this->CurPre['title'])
			)
		);

		$this->doEcho(true);

		$this->resetPreVariables();
	}

	/**
	 * Updates PRE data in the DB.
	 *
	 * @access protected
	 */
	protected function updatePre()
	{
		if (empty($this->CurPre['title'])) {
			return;
		}

		$query = 'UPDATE predb SET ';

		$query .= (!empty($this->CurPre['size'])     ? 'size = '       . $this->db->escapeString($this->CurPre['size'])   . ', ' : '');
		$query .= (!empty($this->CurPre['source'])   ? 'source = '     . $this->db->escapeString($this->CurPre['source']) . ', ' : '');
		$query .= (!empty($this->CurPre['files'])    ? 'files = '      . $this->db->escapeString($this->CurPre['files'])  . ', ' : '');
		$query .= (!empty($this->CurPre['reason'])   ? 'nukereason = ' . $this->db->escapeString($this->CurPre['reason']) . ', ' : '');
		$query .= (!empty($this->CurPre['reqid'])    ? 'requestid = '  . $this->CurPre['reqid']                           . ', ' : '');
		$query .= (!empty($this->CurPre['groupid'])  ? 'groupid = '    . $this->CurPre['groupid']                         . ', ' : '');
		$query .= (!empty($this->CurPre['predate'])  ? 'predate = '    . $this->CurPre['predate']                         . ', ' : '');
		$query .= (!empty($this->CurPre['nuked'])    ? 'nuked = '      . $this->CurPre['nuked']                           . ', ' : '');
		$query .= (
			(empty($this->OldPre['category']) && !empty($this->CurPre['category']))
				? 'category = ' . $this->db->escapeString($this->CurPre['category']) . ', '
				: ''
		);

		if ($query === 'UPDATE predb SET '){
			return;
		}

		$query .= 'title = '      . $this->db->escapeString($this->CurPre['title']);
		$query .= ' WHERE md5 = ' . $this->CurPre['md5'];

		$this->db->ping(true);

		$this->db->queryExec($query);

		$this->doEcho(false);

		$this->resetPreVariables();
	}

	/**
	 * Echo new or update pre to CLI.
	 *
	 * @param bool $new
	 *
	 * @access protected
	 */
	protected function doEcho($new = true)
	{
		if (!$this->silent) {

			$nukeString = '';
			if ($this->nuked !== false) {
				switch((int)$this->CurPre['nuked']) {
					case PreDb::PRE_NUKED:
						$nukeString = '[ NUKED ] ';
						break;
					case PreDb::PRE_UNNUKED:
						$nukeString = '[UNNUKED] ';
						break;
					case PreDb::PRE_MODNUKE:
						$nukeString = '[MODNUKE] ';
						break;
					case PreDb::PRE_OLDNUKE:
						$nukeString = '[OLDNUKE] ';
						break;
					case PreDb::PRE_RENUKED:
						$nukeString = '[RENUKED] ';
						break;
					default:
						break;
				}
				$nukeString .= '[' . $this->CurPre['reason'] . '] ';
			}

			echo
				'[' .
				date('r') .
				($new ? '] [ Added Pre ] [' : '] [Updated Pre] [') .
				$this->CurPre['source'] .
				'] ' .
				 $nukeString .
				'[' .
				$this->CurPre['title'] .
				']' .
				(!empty($this->CurPre['category'])
					? ' [' . $this->CurPre['category'] . ']'
					: (!empty($this->OldPre['category'])
						? ' [' . $this->OldPre['category'] . ']'
						: ''
					)
				) .
				(!empty($this->CurPre['size']) ? ' [' . $this->CurPre['size'] . ']' : '') .
				PHP_EOL;
		}
	}

	/**
	 * Get a group ID for a group name.
	 *
	 * @param string $groupName
	 *
	 * @return mixed
	 *
	 * @access protected
	 */
	protected function getGroupID($groupName)
	{
		if (!isset($this->groupList[$groupName])) {
			$group = $this->db->queryOneRow(sprintf('SELECT id FROM groups WHERE name = %s', $this->db->escapeString($groupName)));
			$this->groupList[$groupName] = $group['id'];
		}
		return $this->groupList[$groupName];
	}

	/**
	 * After updating or inserting new PRE, reset these.
	 *
	 * @access protected
	 */
	protected function resetPreVariables()
	{
		$this->nuked = false;
		$this->OldPre = array();
		$this->CurPre =
			array(
				'title'    => '',
				'md5'      => '',
				'sha1'     => '',
				'size'     => '',
				'predate'  => '',
				'category' => '',
				'source'   => '',
				'groupid'  => '',
				'reqid'    => '',
				'nuked'    => '',
				'reason'   => '',
				'files'    => ''
			);
	}
}
