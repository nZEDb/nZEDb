<?php
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
	 * @var nzedb\db\DB
	 * @access protected
	 */
	protected $db;

	/**
	 * Array of ignored efnet channels.
	 * @var array
	 */
	protected $ignoredEfnet;

	/**
	 * Construct
	 *
	 * @param bool $silent Run this in silent mode (no text output).
	 * @param bool $debug  Turn on debug? Shows sent/received socket buffer messages.
	 *
	 * @access public
	 */
	public function __construct(&$silent = false, &$debug = false)
	{
		if (defined('SCRAPE_IRC_EFNET_CHANNELS_IGNORE')) {
			$this->ignoredEfnet = unserialize(SCRAPE_IRC_EFNET_CHANNELS_IGNORE);
		} else {
			$this->ignoredEfnet = array(
				'#a.b.cd.image'               => false,
				'#a.b.console.ps3'            => false,
				'#a.b.dvd'                    => false,
				'#a.b.erotica'                => false,
				'#a.b.flac'                   => false,
				'#a.b.foreign'                => false,
				'#a.b.games.nintendods'       => false,
				'#a.b.inner-sanctum'          => false,
				'#a.b.moovee'                 => false,
				'#a.b.movies.divx'            => false,
				'#a.b.sony.psp'               => false,
				'#a.b.sounds.mp3.complete_cd' => false,
				'#a.b.teevee'                 => false,
				'#a.b.games.wii'              => false,
				'#a.b.warez'                  => false,
				'#a.b.games.xbox360'          => false,
				'#scnzb'                      => false,
				'#tvnzb'                      => false
			);
		}

		$this->db = new nzedb\db\DB();
		$this->groupList = array();
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

		// Connect to IRC.
		if ($this->connect(SCRAPE_IRC_SERVER, SCRAPE_IRC_PORT, SCRAPE_IRC_TLS) === false) {
			exit (
				'Error connecting to (' .
				SCRAPE_IRC_SERVER .
				':' .
				SCRAPE_IRC_PORT .
				'). Please verify your server information and try again.' .
				PHP_EOL
			);
		}

		// Login to IRC.
		if ($this->login(SCRAPE_IRC_NICKNAME, SCRAPE_IRC_REALNAME, SCRAPE_IRC_USERNAME, SCRAPE_IRC_PASSWORD) === false) {
			exit('Error logging in to: (' .
				SCRAPE_IRC_SERVER . ':' . SCRAPE_IRC_PORT . ') nickname: (' . SCRAPE_IRC_NICKNAME .
				'). Verify your connection information, you might also be banned from this server or there might have been a connection issue.' .
				PHP_EOL
			);
		}

		// Join channels.
		$this->joinChannels(array('#nZEDbPRE' => null));

		if (!$this->silent) {
			echo
				'[' .
				date('r') .
				'] [Scraping of IRC channels for (' .
				SCRAPE_IRC_SERVER .
				':' .
				SCRAPE_IRC_PORT .
				') (' .
				SCRAPE_IRC_NICKNAME .
				') started.]' .
				PHP_EOL;
		}

		// Scan incoming IRC messages.
		$this->readIncoming();
	}

	/**
	 * Process bot messages, insert/update PREs.
	 *
	 * @access protected
	 */
	protected function processChannelMessages()
	{
		if (preg_match(
			'/^(NEW|UPD|NUK): \[DT: (?P<time>.+?)\]\[TT: (?P<title>.+?)\]\[SC: (?P<source>.+?)\]\[CT: (?P<category>.+?)\]' .
			'\[RQ: (?P<req>.+?)\]\[SZ: (?P<size>.+?)\]\[FL: (?P<files>.+?)\](\[(?P<nuked>(UN|MOD|RE|OLD)?NUKED?): (?P<reason>.+?)\])?$/i',
			$this->_channelData['message'], $matches)) {

			if (isset($this->ignoredEfnet[$matches['source']]) && $this->ignoredEfnet[$matches['source']] === true) {
				return;
			}

			$this->CurPre['md5'] = $this->db->escapeString(md5($matches['title']));
			$this->CurPre['sha1'] = $this->db->escapeString(sha1($matches['title']));
			$this->CurPre['predate'] = $this->db->from_unixtime(strtotime($matches['time'] . ' UTC'));
			$this->CurPre['title'] = $matches['title'];
			$this->CurPre['source'] = $matches['source'];
			if ($matches['category'] !== 'N/A') {
				$this->CurPre['category'] = $matches['category'];
			}
			if ($matches['req'] !== 'N/A' && preg_match('/^(?P<req>\d+):(?P<group>.+)$/i', $matches['req'], $matches2)) {
				$this->CurPre['reqid'] = $matches2['req'];
				$this->CurPre['groupid']  = $this->getGroupID($matches2['group']);
			}
			if ($matches['size'] !== 'N/A') {
				$this->CurPre['size'] = $matches['size'];
			}
			if ($matches['files'] !== 'N/A') {
				$this->CurPre['files'] = substr($matches['files'], 0, 50);
			}

			if (isset($matches['nuked'])) {
				switch ($matches['nuked']) {
					case 'NUKED':
						$this->CurPre['nuked'] = PreDb::PRE_NUKED;
						break;
					case 'UNNUKED':
						$this->CurPre['nuked'] = PreDb::PRE_UNNUKED;
						break;
					case 'MODNUKED':
						$this->CurPre['nuked'] = PreDb::PRE_MODNUKE;
						break;
					case 'RENUKED':
						$this->CurPre['nuked'] = PreDb::PRE_RENUKED;
						break;
					case 'OLDNUKE':
						$this->CurPre['nuked'] = PreDb::PRE_OLDNUKE;
						break;
				}
				$this->CurPre['reason'] = (isset($matches['reason']) ? substr($matches['reason'], 0, 255) : '');
			}
			$this->checkForDupe();
		}
	}

	/**
	 * Check if we already have the PRE, update if we have it, insert if not.
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
