<?php
/**
 * $Id$
 * $Revision$
 * $Author$
 * $Date$
 *
 * Net_SmartIRC
 * This is a PHP class for communication with IRC networks,
 * which conforms to the RFC 2812 (IRC protocol).
 * It's an API that handles all IRC protocol messages.
 * This class is designed for creating IRC bots, chats and showing irc related
 * info on webpages.
 *
 * Documentation, a HOWTO, and examples are included in SmartIRC.
 *
 * Here you will find a service bot which I am also developing
 * <http://cvs.meebey.net/atbs> and <http://cvs.meebey.net/phpbitch>
 * Latest versions of Net_SmartIRC you will find on the project homepage
 * or get it through PEAR since SmartIRC is an official PEAR package.
 * See <http://pear.php.net/Net_SmartIRC>.
 *
 * Official Project Homepage: <http://sf.net/projects/phpsmartirc>
 *
 * Net_SmartIRC conforms to RFC 2812 (Internet Relay Chat: Client Protocol)
 *
 * Copyright (c) 2002-2005 Mirco Bauer <meebey@meebey.net> <http://www.meebey.net>
 *
 * Full LGPL License: <http://www.gnu.org/licenses/lgpl.txt>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
// ------- PHP code ----------
require_once 'SmartIRC/defines.php';
define('SMARTIRC_VERSION', '1.1.0-dev ($Revision$)');
define('SMARTIRC_VERSIONSTRING', 'Net_SmartIRC '.SMARTIRC_VERSION);

/**
 * main SmartIRC class
 *
 * @package Net_SmartIRC
 * @version 0.6.0-dev
 * @author Mirco 'meebey' Bauer <mail@meebey.net>
 * @access public
 */
class Net_SmartIRC_base
{
	/**
	 * @var resource
	 * @access private
	 */
	protected $_socket;

	/**
	 * @var string
	 * @access public
	 */
	public $_address;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_port;

	/**
	 * @var string
	 * @access private
	 */
	protected $_bindaddress = null;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_bindport = 0;

	/**
	 * @var string
	 * @access private
	 */
	protected $_nick;

	/**
	 * @var string
	 * @access private
	 */
	protected $_username;

	/**
	 * @var string
	 * @access private
	 */
	protected $_realname;

	/**
	 * @var string
	 * @access private
	 */
	protected $_usermode;

	/**
	 * @var string
	 * @access private
	 */
	protected $_password;

	/**
	 * @var array
	 * @access private
	 */
	protected $_performs = array();

	/**
	 * @var boolean
	 * @access private
	 */
	protected $_state = false;

	/**
	 * @var array
	 * @access private
	 */
	protected $_actionhandler = array();

	/**
	 * @var array
	 * @access private
	 */
	protected $_timehandler = array();

	/**
	 * @var integer
	 * @access private
	 */
	protected $_debug = SMARTIRC_DEBUG_NOTICE;

	/**
	 * @var array
	 * @access private
	 */
	protected $_messagebuffer = array();

	/**
	 * @var integer
	 * @access private
	 */
	protected $_messagebuffersize;

	/**
	 * @var boolean
	 * @access private
	 */
	protected $_usesockets = false;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_receivedelay = 100;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_senddelay = 250;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_logdestination = SMARTIRC_STDOUT;

	/**
	 * @var resource
	 * @access private
	 */
	protected $_logfilefp = 0;

	/**
	 * @var string
	 * @access private
	 */
	protected $_logfile = 'Net_SmartIRC.log';

	/**
	 * @var integer
	 * @access private
	 */
	protected $_disconnecttime = 1000;

	/**
	 * @var boolean
	 * @access private
	 */
	protected $_loggedin = false;

	/**
	 * @var boolean
	 * @access private
	 */
	protected $_benchmark = false;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_benchmark_starttime;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_benchmark_stoptime;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_actionhandlerid = 0;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_timehandlerid = 0;

	/**
	 * @var array
	 * @access private
	 */
	protected $_motd = array();

	/**
	 * @var array
	 * @access private
	 */
	protected $_channels = array();

	/**
	 * @var boolean
	 * @access private
	 */
	protected $_channelsyncing = false;

	/**
	 * @var array
	 * @access private
	 */
	protected $_users = array();

	/**
	 * @var boolean
	 * @access private
	 */
	protected $_usersyncing = false;

	/**
	 * Stores the path to the modules that can be loaded.
	 *
	 * @var string
	 * @access private
	 */
	protected $_modulepath = '';

	/**
	 * Stores all objects of the modules.
	 *
	 * @var string
	 * @access private
	 */
	protected $_modules = array();

	/**
	 * @var string
	 * @access private
	 */
	protected $_ctcpversion;

	/**
	 * @var mixed
	 * @access private
	 */
	protected $_mintimer = false;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_maxtimer = 300000;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_txtimeout = 300;

	/**
	 * @var integer
	 * @access public
	 */
	public $_rxtimeout = 300;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_selecttimeout;

	/**
	 * @var integer
	 * @access public
	 */
	public $_lastrx;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_lasttx;

	/**
	 * @var boolean
	 * @access private
	 */
	protected $_autoreconnect = false;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_reconnectdelay = 10000;

	/**
	 * @var boolean
	 * @access private
	 */
	protected $_autoretry = false;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_autoretrymax = 5;

	/**
	 * @var integer
	 * @access private
	 */
	protected $_autoretrycount = 0;

	/**
	 * @var boolean
	 * @access private
	 */
	protected $_connectionerror = false;

	/**
	 * @var boolean
	 * @access  private
	 */
	protected $_runasdaemon = false;

	/**
	 * @var array
	 */
	protected $channelList = array();

	/**
	 * All IRC replycodes, the index is the replycode name.
	 *
	 * @see $SMARTIRC_replycodes
	 * @var array
	 * @access public
	 */
	public $replycodes;

	/**
	 * All numeric IRC replycodes, the index is the numeric replycode.
	 *
	 * @see $SMARTIRC_nreplycodes
	 * @var array
	 * @access public
	 */
	public $nreplycodes;

	/**
	 * Stores all channels in this array where we are joined, works only if channelsyncing is activated.
	 * Eg. for accessing a user, use it like this: (in this example the SmartIRC object is stored in $irc)
	 * $irc->channel['#test']->users['meebey']->nick;
	 *
	 * @see setChannelSyncing()
	 * @see Net_SmartIRC_channel
	 * @see Net_SmartIRC_channeluser
	 * @var array
	 * @access public
	 */
	public $channel;

	/**
	 * Stores all users that had/have contact with us (channel/query/notice etc.), works only if usersyncing is activated.
	 * Eg. for accessing a user, use it like this: (in this example the SmartIRC object is stored in $irc)
	 * $irc->user['meebey']->host;
	 *
	 * @see setUserSyncing()
	 * @see Net_SmartIRC_ircuser
	 * @var array
	 * @access public
	 */
	public $user;

	/**
	 * Constructor. Initiates the messagebuffer and "links" the replycodes from
	 * global into properties. Also some PHP runtime settings are configured.
	 *
	 * @access public
	 */
	public function __construct()
	{
		ob_implicit_flush(true);
		@set_time_limit(0);
		$this->_messagebuffer[SMARTIRC_CRITICAL] = array();
		$this->_messagebuffer[SMARTIRC_HIGH] = array();
		$this->_messagebuffer[SMARTIRC_MEDIUM] = array();
		$this->_messagebuffer[SMARTIRC_LOW] = array();

		$this->replycodes = &$GLOBALS['SMARTIRC_replycodes'];
		$this->nreplycodes = &$GLOBALS['SMARTIRC_nreplycodes'];

		// hack till PHP allows (PHP5) $object->somemethod($param)->memberofobject
		$this->channel = &$this->_channels;
		// another hack
		$this->user = &$this->_users;

		if (isset($_SERVER['REQUEST_METHOD'])) {
			// the script is called from a browser, lets set default log destination
			// to SMARTIRC_BROWSEROUT (makes browser friendly output)
			$this->setLogdestination(SMARTIRC_BROWSEROUT);
		}
	}

	/**
	 * Enables/disables the usage of real sockets.
	 *
	 * Enables/disables the usage of real sockets instead of fsocks
	 * (works only if your PHP build has loaded the PHP socket extension)
	 * Default: false
	 *
	 * @param bool $boolean
	 * @return void
	 * @access public
	 */
	public function setUseSockets($boolean)
	{
		$this->_usesockets = false;
		if ($boolean === true) {
			if (@extension_loaded('sockets')) {
				$this->_usesockets = true;
			} else {
				$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: your PHP build doesn\'t support real sockets, will use fsocks instead', __FILE__, __LINE__);
			}
		}
	}

	/**
	 * Sets an IP address (and optionally, a port) to bind the socket to.
	 *
	 * Limits the bot to claiming only one of the machine's IPs as its home.
	 * Only works with setUseSockets(TRUE). Call with no parameters to unbind.
	 *
	 * @param string $addr
	 * @param int $port
	 * @return bool
	 * @access public
	 */
	public function setBindAddress($addr=null, $port=0)
	{
		if ($this->_usesockets) {
			$this->_bindaddress = $addr;
			$this->_bindport = $port;
		}
		return $this->_usesockets;
	}

	/**
	 * Sets the level of debug messages.
	 *
	 * Sets the debug level (bitwise), useful for testing/developing your code.
	 * Here the list of all possible debug levels:
	 * SMARTIRC_DEBUG_NONE
	 * SMARTIRC_DEBUG_NOTICE
	 * SMARTIRC_DEBUG_CONNECTION
	 * SMARTIRC_DEBUG_SOCKET
	 * SMARTIRC_DEBUG_IRCMESSAGES
	 * SMARTIRC_DEBUG_MESSAGETYPES
	 * SMARTIRC_DEBUG_ACTIONHANDLER
	 * SMARTIRC_DEBUG_TIMEHANDLER
	 * SMARTIRC_DEBUG_MESSAGEHANDLER
	 * SMARTIRC_DEBUG_CHANNELSYNCING
	 * SMARTIRC_DEBUG_MODULES
	 * SMARTIRC_DEBUG_USERSYNCING
	 * SMARTIRC_DEBUG_ALL
	 *
	 * Default: SMARTIRC_DEBUG_NOTICE
	 *
	 * @see DOCUMENTATION
	 * @see SMARTIRC_DEBUG_NOTICE
	 * @param integer $level
	 * @return void
	 * @access public
	 */
	public function setDebug($level)
	{
		$this->_debug = $level;
	}

	/**
	 * Enables/disables the benchmark engine.
	 *
	 * @param boolean $boolean
	 * @return void
	 * @access public
	 */
	public function setBenchmark($boolean)
	{
		if (is_bool($boolean)) {
			$this->_benchmark = $boolean;
		} else {
			$this->_benchmark = false;
		}
	}

	/**
	 * Enables/disables channel syncing.
	 *
	 * Channel syncing means, all users on all channel we are joined are tracked in the
	 * channel array. This makes it very handy for botcoding.
	 *
	 * @param boolean $boolean
	 * @return void
	 * @access public
	 */
	public function setChannelSyncing($boolean)
	{
		if (is_bool($boolean)) {
			$this->_channelsyncing = $boolean;
		} else {
			$this->_channelsyncing = false;
		}

		if ($this->_channelsyncing == true) {
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: Channel syncing enabled', __FILE__, __LINE__);
		} else {
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: Channel syncing disabled', __FILE__, __LINE__);
		}
	}

	/**
	 * Enables/disables user syncing.
	 *
	 * User syncing means, all users we have or had contact with through channel, query or
	 * notice are tracked in the $irc->user array. This is very handy for botcoding.
	 *
	 * @param boolean $boolean
	 * @return void
	 * @access public
	 */
	public function setUserSyncing($boolean)
	{
		if (is_bool($boolean)) {
			$this->_usersyncing = $boolean;
		} else {
			$this->_usersyncing = false;
		}

		if ($this->_usersyncing == true) {
			$this->log(SMARTIRC_DEBUG_USERSYNCING, 'DEBUG_USERSYNCING: User syncing enabled', __FILE__, __LINE__);
		} else {
			$this->log(SMARTIRC_DEBUG_USERSYNCING, 'DEBUG_USERSYNCING: User syncing disabled', __FILE__, __LINE__);
		}
	}

	/**
	 * Sets the CTCP version reply string.
	 *
	 * @param string $versionstring
	 * @return void
	 * @access public
	 */
	public function setCtcpVersion($versionstring)
	{
		$this->_ctcpversion = $versionstring;
	}

	/**
	 * Sets the destination of all log messages.
	 *
	 * Sets the destination of log messages.
	 * $type can be:
	 * SMARTIRC_FILE for saving the log into a file
	 * SMARTIRC_STDOUT for echoing the log to stdout
	 * SMARTIRC_SYSLOG for sending the log to the syslog
	 * Default: SMARTIRC_STDOUT
	 *
	 * @see SMARTIRC_STDOUT
	 * @param integer $type must be on of the constants
	 * @return void
	 * @access public
	 */
	public function setLogdestination($type)
	{
		switch ($type) {
			case SMARTIRC_FILE:
			case SMARTIRC_STDOUT:
			case SMARTIRC_SYSLOG:
			case SMARTIRC_BROWSEROUT:
			case SMARTIRC_NONE:
				$this->_logdestination = $type;
			break;
			default:
				$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: unknown logdestination type ('.$type.'), will use STDOUT instead', __FILE__, __LINE__);
				$this->_logdestination = SMARTIRC_STDOUT;
		}
	}

	/**
	 * Sets the file for the log if the destination is set to file.
	 *
	 * Sets the logfile, if {@link setLogdestination logdestination} is set to SMARTIRC_FILE.
	 * This should be only used with full path!
	 *
	 * @param string $file
	 * @return void
	 * @access public
	 */
	public function setLogfile($file)
	{
		$this->_logfile = $file;
	}

	/**
	 * Sets the delaytime before closing the socket when disconnect.
	 *
	 * @param integer $milliseconds
	 * @return void
	 * @access public
	 */
	public function setDisconnecttime($milliseconds)
	{
		if (is_integer($milliseconds) && $milliseconds >= 100) {
			$this->_disconnecttime = $milliseconds;
		} else {
			$this->_disconnecttime = 100;
		}
	}

	/**
	 * Sets the delaytime before attempting reconnect.
	 * Value of 0 disables the delay entirely.
	 *
	 * @param integer $milliseconds
	 * @return void
	 * @access public
	 */
	public function setReconnectdelay($milliseconds)
	{
		if (is_integer($milliseconds)) {
			$this->_reconnectdelay = $milliseconds;
		} else {
			$this->_reconnectdelay = 10000;
		}
	}

	/**
	 * Sets the delay for receiving data from the IRC server.
	 *
	 * Sets the delaytime between messages that are received, this reduces your CPU load.
	 * Don't set this too low (min 100ms).
	 * Default: 100
	 *
	 * @param integer $milliseconds
	 * @return void
	 * @access public
	 */
	public function setReceivedelay($milliseconds)
	{
		if (is_integer($milliseconds) && $milliseconds >= 100) {
			$this->_receivedelay = $milliseconds;
		} else {
			$this->_receivedelay = 100;
		}
	}

	/**
	 * Sets the delay for sending data to the IRC server.
	 *
	 * Sets the delaytime between messages that are sent, because IRC servers doesn't like floods.
	 * This will avoid sending your messages too fast to the IRC server.
	 * Default: 250
	 *
	 * @param integer $milliseconds
	 * @return void
	 * @access public
	 */
	public function setSenddelay($milliseconds)
	{
		if (is_integer($milliseconds)) {
			$this->_senddelay = $milliseconds;
		} else {
			$this->_senddelay = 250;
		}
	}

	/**
	 * Enables/disables autoreconnecting.
	 *
	 * @param boolean $boolean
	 * @return void
	 * @access public
	 */
	public function setAutoReconnect($boolean)
	{
		if (is_bool($boolean)) {
			$this->_autoreconnect = $boolean;
		} else {
			$this->_autoreconnect = false;
		}
	}

	/**
	 * Enables/disables autoretry for connecting to a server.
	 *
	 * @param boolean $boolean
	 * @return void
	 * @access public
	 */
	public function setAutoRetry($boolean)
	{
		if (is_bool($boolean)) {
			$this->_autoretry = $boolean;
		} else {
			$this->_autoretry = false;
		}
	}

	/**
	 * Sets the maximum number of attempts to connect to a server
	 * before giving up.
	 *
	 * @param integer $autoretrymax
	 * @return void
	 * @access public
	 */
	public function setAutoRetryMax($autoretrymax)
	{
		if (is_integer($autoretrymax)) {
			$this->_autoretrymax = $autoretrymax;
		} else {
			$this->_autoretrymax = 5;
		}
	}

	/**
	 * Sets the receive timeout.
	 *
	 * If the timeout occurs, the connection will be reinitialized
	 * Default: 300 seconds
	 *
	 * @param integer $seconds
	 * @return void
	 * @access public
	 */
	public function setReceiveTimeout($seconds)
	{
		if (is_integer($seconds)) {
			$this->_rxtimeout = $seconds;
		} else {
			$this->_rxtimeout = 300;
		}
	}

	/**
	 * Sets the transmit timeout.
	 *
	 * If the timeout occurs, the connection will be reinitialized
	 * Default: 300 seconds
	 *
	 * @param integer $seconds
	 * @return void
	 * @access public
	 */
	public function setTransmitTimeout($seconds)
	{
		if (is_integer($seconds)) {
			$this->_txtimeout = $seconds;
		} else {
			$this->_txtimeout = 300;
		}
	}

	/**
	 * Sets the paths for the modules.
	 *
	 * @param integer $path
	 * @return void
	 * @access public
	 */
	public function setModulepath($path)
	{
		$this->_modulepath = $path;
	}

	/**
	 * Sets wheter the script should be run as a daemon or not
	 * ( actually disables/enables ignore_user_abort() )
	 *
	 * @param boolean $boolean
	 * @return void
	 * @access public
	 */
	public function setRunAsDaemon($boolean)
	{
		if ($boolean === true) {
			$this->_runasdaemon = true;
			ignore_user_abort(true);
			set_time_limit(0);
		} else {
			$this->_runasdaemon = false;
		}
	}

	/**
	 * Starts the benchmark (sets the counters).
	 *
	 * @return void
	 * @access public
	 */
	public function startBenchmark()
	{
		$this->_benchmark_starttime = $this->_microint();
		$this->log(SMARTIRC_DEBUG_NOTICE, 'benchmark started', __FILE__, __LINE__);
	}

	/**
	 * Stops the benchmark and displays the result.
	 *
	 * @return void
	 * @access public
	 */
	public function stopBenchmark()
	{
		$this->_benchmark_stoptime = $this->_microint();
		$this->log(SMARTIRC_DEBUG_NOTICE, 'benchmark stopped', __FILE__, __LINE__);

		if ($this->_benchmark) {
			$this->showBenchmark();
		}
	}

	/**
	 * Shows the benchmark result.
	 *
	 * @return void
	 * @access public
	 */
	public function showBenchmark()
	{
		$this->log(SMARTIRC_DEBUG_NOTICE, 'benchmark time: '.((float)$this->_benchmark_stoptime-(float)$this->_benchmark_starttime), __FILE__, __LINE__);
	}

	/**
	 * Adds an entry to the log.
	 *
	 * Adds an entry to the log with Linux style log format.
	 * Possible $level constants (can also be combined with "|"s)
	 * SMARTIRC_DEBUG_NONE
	 * SMARTIRC_DEBUG_NOTICE
	 * SMARTIRC_DEBUG_CONNECTION
	 * SMARTIRC_DEBUG_SOCKET
	 * SMARTIRC_DEBUG_IRCMESSAGES
	 * SMARTIRC_DEBUG_MESSAGETYPES
	 * SMARTIRC_DEBUG_ACTIONHANDLER
	 * SMARTIRC_DEBUG_TIMEHANDLER
	 * SMARTIRC_DEBUG_MESSAGEHANDLER
	 * SMARTIRC_DEBUG_CHANNELSYNCING
	 * SMARTIRC_DEBUG_MODULES
	 * SMARTIRC_DEBUG_USERSYNCING
	 * SMARTIRC_DEBUG_ALL
	 *
	 * @see SMARTIRC_DEBUG_NOTICE
	 * @param integer $level bit constants (SMARTIRC_DEBUG_*)
	 * @param string $file
	 * @param string $entry the new log entry
	 * @param string $line
	 * @return void
	 * @access protected
	 */
	protected function log($level, $entry, $file = null, $line = null)
	{
		// prechecks
		if (!(is_integer($level)) ||
			!($level & SMARTIRC_DEBUG_ALL)) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: invalid log level passed to log() ('.$level.')', __FILE__, __LINE__);
			return;
		}

		if (!($level & $this->_debug) ||
			 ($this->_logdestination == SMARTIRC_NONE)) {
			return;
		}

		if (substr($entry, -1) != "\n") {
			$entry .= "\n";
		}

		if ($file !== null &&
			$line !== null) {
			$file = basename($file);
			$entry = $file.'('.$line.') '.$entry;
		} else {
			$entry = 'unknown(0) '.$entry;
		}

		$formatedentry = date('M d H:i:s ').$entry;
		switch ($this->_logdestination) {
			case SMARTIRC_STDOUT:
				echo $formatedentry;
				flush();
			break;
			case SMARTIRC_BROWSEROUT:
				echo '<pre>'.htmlentities($formatedentry).'</pre>';
			break;
			case SMARTIRC_FILE:
				if (!is_resource($this->_logfilefp)) {
					if ($this->_logfilefp === null) {
						// we reconncted and don't want to destroy the old log entries
						$this->_logfilefp = fopen($this->_logfile,'a');
					} else {
						$this->_logfilefp = fopen($this->_logfile,'w');
					}
				}
				fwrite($this->_logfilefp, $formatedentry);
				fflush($this->_logfilefp);
			break;
			case SMARTIRC_SYSLOG:
				if (!is_int($this->_logfilefp)) {
					$this->_logfilefp = openlog('Net_SmartIRC', LOG_NDELAY, LOG_DAEMON);
				}
				syslog(LOG_INFO, $entry);
			break;
		}
	}

	/**
	 * Returns the full motd.
	 *
	 * @return array
	 * @access public
	 */
	public function getMotd()
	{
		return $this->_motd;
	}

	/**
	 * Returns the usermode.
	 *
	 * @return string
	 * @access public
	 */
	public function getUsermode()
	{
		return $this->_usermode;
	}

	/**
	 * Returns a reference to the channel object of the specified channelname.
	 *
	 * @param string $channelname
	 * @return object
	 * @access public
	 */
	public function getChannel($channelname)
	{
		if ($this->_channelsyncing != true) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: getChannel() is called and the required Channel Syncing is not activated!', __FILE__, __LINE__);
			return false;
		}

		if ($this->isJoined($channelname)) {
			return $this->_channels[strtolower($channelname)];
		} else {
			return false;
		}
	}

	/**
	 * Returns a reference to the user object for the specified username and channelname.
	 *
	 * @param string $channelname
	 * @param string $username
	 * @return object
	 * @access public
	 */
	public function getUser($channelname, $username)
	{
		if ($this->_channelsyncing != true) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: getUser() is called and the required Channel Syncing is not activated!', __FILE__, __LINE__);
			return false;
		}

		if ($this->isJoined($channelname, $username)) {
			return $this->_channels[strtolower($channelname)]->users[strtolower($username)];
		} else {
			return false;
		}
	}

	/**
	 * Creates the sockets and connects to the IRC server on the given port.
	 *
	 * @param string $address
	 * @param integer $port
	 * @return boolean
	 * @access public
	 */
	public function connect($address, $port)
	{
		$this->_address = $address;
		$this->_port = $port;
		$this->_autoretrycount = 0;
		$connected = false;
		if ($this->_autoretry === true && ($this->_autoretrycount < $this->_autoretrymax)) {
			while ($this->_autoretrycount < $this->_autoretrymax) {
				$connected = $this->connectHelper($address, $port);
				if ($connected === true) {
					break;
				} else {
					$this->_delayReconnect();
					$this->_autoretrycount++;
				}
			}
		} else {
			$connected = $this->connectHelper($address, $port);
		}
		if ($connected === false) {
			$this->_connectionerror = true;
		}
		return $connected;
	}

	/**
	 * Helper method for connecting.
	 *
	 * @return boolean
	 * @access protected
	 */
	protected function connectHelper()
	{
		$this->log(SMARTIRC_DEBUG_CONNECTION, 'DEBUG_CONNECTION: connecting', __FILE__, __LINE__);
		$errno = $errstr = 0;

		if ($this->_usesockets == true) {
			$this->log(SMARTIRC_DEBUG_SOCKET, 'DEBUG_SOCKET: using real sockets', __FILE__, __LINE__);
			$this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if ($this->_bindaddress !== null) {
				if (socket_bind($this->_socket, $this->_bindaddress, $this->_bindport)) {
					$this->log(SMARTIRC_DEBUG_SOCKET,
						'DEBUG_SOCKET: bound to '.$this->_bindaddress.':'
						.$this->_bindport, __FILE__, __LINE__);
				} else {
					$errno = socket_last_error($this->_socket);

					$error_msg =
						'ERROR: Unable to bind '.$this->_bindaddress.':'
						.$this->_bindport.' reason: '.socket_strerror($errno)
						.' ('.$errno.')';

					$this->log(SMARTIRC_DEBUG_NOTICE, 'Warning: '.$error_msg, __FILE__, __LINE__);
					echo $error_msg . PHP_EOL;
					return false;
				}
			}
			$result = @socket_connect($this->_socket, $this->_address, $this->_port);
		} else {
			$this->log(SMARTIRC_DEBUG_SOCKET, 'DEBUG_SOCKET: using fsockets', __FILE__, __LINE__);
			$result = fsockopen($this->_address, $this->_port, $errno, $errstr);
		}

		if ($result === false) {
			if ($this->_usesockets == true) {
				$error = socket_strerror(socket_last_error($this->_socket));
			} else {
				$error = $errstr.' ('.$errno.')';
			}

			$error_msg = 'couldn\'t connect to "'.$this->_address.'" reason: "'.$error.'"';
			$this->log(SMARTIRC_DEBUG_NOTICE, 'Warning: '.$error_msg, __FILE__, __LINE__);

			if (($this->_autoretry == true) &&
				($this->_autoretrycount < $this->_autoretrymax)) {
				 echo 'ERROR connecting to (' . $this->_address .  ':' . $this->_port .
					 ') error: (' . $error . ') retry (' . $this->_autoretrycount .
					 '/' . $this->_autoretrymax .
					 '). Sleeping for (' . $this->_reconnectdelay . ') ms.' . PHP_EOL;
				return false;
			} else {
				echo 'ERROR connecting to (' . $this->_address . ':' .  $this->_port .
					') after (' . $this->_autoretrymax . ') retries, error: (' .
					$error . ').' . PHP_EOL;
				return false;
			}
		} else {
			$this->log(SMARTIRC_DEBUG_CONNECTION, 'DEBUG_CONNECTION: connected', __FILE__, __LINE__);
			$this->_autoretrycount = 0;
			$this->_connectionerror = false;

			if ($this->_usesockets != true) {
				$this->_socket = $result;
				$this->log(SMARTIRC_DEBUG_SOCKET, 'DEBUG_SOCKET: activating nonblocking fsocket mode', __FILE__, __LINE__);
				stream_set_blocking($this->_socket, 0);
			}
		}

		$this->_lastrx = $this->_lasttx = time();
		$this->_updatestate();

		return $result;
	}

	/**
	 * Disconnects from the IRC server nicely with a QUIT or just destroys the socket.
	 *
	 * Disconnects from the IRC server in the given quickness mode.
	 * $quickdisconnect:
	 * true, just close the socket
	 * false, send QUIT and wait {@link $_disconnectime $_disconnectime} before closing the socket
	 *
	 * @param boolean $quickdisconnect default: false
	 * @return boolean
	 * @access public
	 */
	public function disconnect($quickdisconnect = false)
	{
		if ($this->_connectionerror === false && $this->_state() == SMARTIRC_STATE_CONNECTED) {
			if ($quickdisconnect == false) {
				$this->_send('QUIT', SMARTIRC_CRITICAL);
				usleep($this->_disconnecttime*1000);
			}

			if ($this->_usesockets == true) {
				@socket_shutdown($this->_socket);
				@socket_close($this->_socket);
			} else {
				fclose($this->_socket);
			}

			$this->_updatestate();
			$this->log(SMARTIRC_DEBUG_CONNECTION, 'DEBUG_CONNECTION: disconnected', __FILE__, __LINE__);
		}

		if ($this->_channelsyncing == true) {
			// let's clean our channel array
			$this->_channels = array();
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: cleaned channel array', __FILE__, __LINE__);
		}

		if ($this->_usersyncing == true) {
			// let's clean our user array
			$this->_users = array();
			$this->log(SMARTIRC_DEBUG_USERSYNCING, 'DEBUG_USERSYNCING: cleaned user array', __FILE__, __LINE__);
		}

		if ($this->_logdestination == SMARTIRC_FILE) {
			fclose($this->_logfilefp);
			$this->_logfilefp = null;
		} else if ($this->_logdestination == SMARTIRC_SYSLOG) {
			closelog();
		}

		return true;
	}

	/**
	 * Reconnects to the IRC server with the same login info,
	 * it also rejoins the channels
	 *
	 * @return void
	 * @access public
	 */
	public function reconnect()
	{
		$this->log(SMARTIRC_DEBUG_CONNECTION, 'DEBUG_CONNECTION: reconnecting...', __FILE__, __LINE__);
		$this->disconnect(true);
		$this->connect($this->_address, $this->_port);
		$this->login($this->_nick, $this->_realname, $this->_usermode, $this->_username, $this->_password);
		$this->joinChannels($this->channelList);
	}

	/**
	 * Join one or more channel.
	 *
	 * @param array $channelarray array('#channelname1' => 'password', '#channelname2' => null);
	 * @param int   $priority
	 *
	 * @return bool
	 */
	public function joinChannels($channelarray, $priority = SMARTIRC_MEDIUM)
	{
		if ($this->_connectionerror === true || $this->_state() === SMARTIRC_STATE_DISCONNECTED)  {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'Warning: you must connect to IRC before joining channels!', __FILE__, __LINE__);
			return false;
		}

		$this->channelList = $channelarray;
		return $this->join($channelarray, $priority);
	}

	/**
	 * login and register nickname on the IRC network
	 *
	 * Registers the nickname and user information on the IRC network.
	 *
	 * @param string $nick
	 * @param string $realname
	 * @param integer $usermode
	 * @param string $username
	 * @param string $password
	 * @return bool
	 * @access public
	 */
	public function login($nick, $realname, $usermode = 0, $username = null, $password = null)
	{
		if ($this->_connectionerror === true || $this->_state() === SMARTIRC_STATE_DISCONNECTED)  {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'Warning: you must connect to IRC before logging in!', __FILE__, __LINE__);
			return false;
		}

		$this->log(SMARTIRC_DEBUG_CONNECTION, 'DEBUG_CONNECTION: logging in', __FILE__, __LINE__);

		$this->_nick = str_replace(' ', '', $nick);
		$this->_realname = $realname;

		if ($username !== null) {
			$this->_username = str_replace(' ', '', $username);
		} else {
			$this->_username = str_replace(' ', '', exec('whoami'));
		}

		if ($password !== null) {
			$this->_password = $password;
			$this->_send('PASS '.$this->_password, SMARTIRC_CRITICAL);
		}

		if (!is_numeric($usermode)) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'Warning: login() usermode ('.$usermode.') is not valid, will use 0 instead', __FILE__, __LINE__);
			$usermode = 0;
		}
		$this->_usermode = $usermode;

		$this->_send('NICK '.$this->_nick, SMARTIRC_CRITICAL);
		$this->_send('USER '.$this->_username.' '.$this->_usermode.' '.SMARTIRC_UNUSED.' :'.$this->_realname, SMARTIRC_CRITICAL);

		if (count($this->_performs)) {
			// if we have extra commands to send, do it now
			foreach($this->_performs as $command) {
				$this->_send($command, SMARTIRC_HIGH);
			}
			// if we sent "ns auth" commands, we may need to resend our nick
			$this->_send('NICK '.$this->_nick, SMARTIRC_HIGH);
		}
		return true;
	}

	// </IRC methods>

	/**
	 * adds a command to the list of commands to be sent after login() info
	 *
	 * @param string $cmd the command to add to the perform list
	 * @access public
	 */
	public function perform($cmd)
	{
		$this->_performs[] = $cmd;
	}

	/**
	 * checks if the passed nickname is our own nickname
	 *
	 * @param string $nickname
	 * @return boolean
	 * @access public
	 */
	public function isMe($nickname)
	{
		if ($nickname == $this->_nick) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * checks if we or the given user is joined to the specified channel and returns the result
	 * ChannelSyncing is required for this.
	 *
	 * @see setChannelSyncing
	 * @param string $channel
	 * @param string $nickname
	 * @return boolean
	 * @access public
	 */
	public function isJoined($channel, $nickname = null)
	{
		if ($this->_channelsyncing != true) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'Warning: isJoined() is called and the required Channel Syncing is not activated!', __FILE__, __LINE__);
			return false;
		}

		if ($nickname === null) {
			$nickname = $this->_nick;
		}

		if (isset($this->_channels[strtolower($channel)]->users[strtolower($nickname)])) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if we or the given user is founder on the specified channel and returns the result.
	 * ChannelSyncing is required for this.
	 *
	 * @see setChannelSyncing
	 * @param string $channel
	 * @param string $nickname
	 * @return boolean
	 * @access public
	 */
	public function isFounder($channel, $nickname = null)
	{
		if ($this->_channelsyncing != true) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: isFounder() is called and the required Channel Syncing is not activated!', __FILE__, __LINE__);
			return false;
		}

		if ($nickname === null) {
			$nickname = $this->_nick;
		}

		if ($this->isJoined($channel, $nickname)) {
			if ($this->_channels[strtolower($channel)]->users[strtolower($nickname)]->founder) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if we or the given user is admin on the specified channel and returns the result.
	 * ChannelSyncing is required for this.
	 *
	 * @see setChannelSyncing
	 * @param string $channel
	 * @param string $nickname
	 * @return boolean
	 * @access public
	 */
	public function isAdmin($channel, $nickname = null)
	{
		if ($this->_channelsyncing != true) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: isAdmin() is called and the required Channel Syncing is not activated!', __FILE__, __LINE__);
			return false;
		}

		if ($nickname === null) {
			$nickname = $this->_nick;
		}

		if ($this->isJoined($channel, $nickname)) {
			if ($this->_channels[strtolower($channel)]->users[strtolower($nickname)]->admin) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if we or the given user is opped on the specified channel and returns the result.
	 * ChannelSyncing is required for this.
	 *
	 * @see setChannelSyncing
	 * @param string $channel
	 * @param string $nickname
	 * @return boolean
	 * @access public
	 */
	public function isOpped($channel, $nickname = null)
	{
		if ($this->_channelsyncing != true) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: isOpped() is called and the required Channel Syncing is not activated!', __FILE__, __LINE__);
			return false;
		}

		if ($nickname === null) {
			$nickname = $this->_nick;
		}

		if ($this->isJoined($channel, $nickname)) {
			if ($this->_channels[strtolower($channel)]->users[strtolower($nickname)]->op) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if we or the given user is hopped on the specified channel and returns the result.
	 * ChannelSyncing is required for this.
	 *
	 * @see setChannelSyncing
	 * @param string $channel
	 * @param string $nickname
	 * @return boolean
	 * @access public
	 */
	public function isHopped($channel, $nickname = null)
	{
		if ($this->_channelsyncing != true) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: isHopped() is called and the required Channel Syncing is not activated!', __FILE__, __LINE__);
			return false;
		}

		if ($nickname === null) {
			$nickname = $this->_nick;
		}

		if ($this->isJoined($channel, $nickname)) {
			if ($this->_channels[strtolower($channel)]->users[strtolower($nickname)]->hop) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if we or the given user is voiced on the specified channel and returns the result.
	 * ChannelSyncing is required for this.
	 *
	 * @see setChannelSyncing
	 * @param string $channel
	 * @param string $nickname
	 * @return boolean
	 * @access public
	 */
	public function isVoiced($channel, $nickname = null)
	{
		if ($this->_channelsyncing != true) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: isVoiced() is called and the required Channel Syncing is not activated!', __FILE__, __LINE__);
			return false;
		}

		if ($nickname === null) {
			$nickname = $this->_nick;
		}

		if ($this->isJoined($channel, $nickname)) {
			if ($this->_channels[strtolower($channel)]->users[strtolower($nickname)]->voice) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the hostmask is on the specified channel banned and returns the result.
	 * ChannelSyncing is required for this.
	 *
	 * @see setChannelSyncing
	 * @param string $channel
	 * @param string $hostmask
	 * @return boolean
	 * @access public
	 */
	public function isBanned($channel, $hostmask)
	{
		if ($this->_channelsyncing != true) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: isBanned() is called and the required Channel Syncing is not activated!', __FILE__, __LINE__);
			return false;
		}

		if ($this->isJoined($channel)) {
			$result = array_search($hostmask, $this->_channels[strtolower($channel)]->bans);

			if ($result !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * goes into receive mode
	 *
	 * Goes into receive and idle mode. Only call this if you want to "spawn" the bot.
	 * No further lines of PHP code will be processed after this call, only the bot methods!
	 *
	 * @return boolean
	 * @access public
	 */
	public function listen()
	{
		while (true) {
			if ($this->listenOnce() === false) {
				break;
			}
		}

		return false;
	}

	/**
	 * goes into receive mode _only_ for one pass
	 *
	 * Goes into receive mode. It will return when one pass is complete.
	 * Use this when you want to connect to multiple IRC servers.
	 *
	 * @return boolean
	 * @access public
	 */
	public function listenOnce()
	{
		if ($this->checkConnection()) {
			$this->_rawreceive();
			if ($this->checkConnection()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if the connection is still up.
	 *
	 * @return bool
	 */
	protected function checkConnection()
	{
		if ($this->_connectionerror || $this->_state() === SMARTIRC_STATE_DISCONNECTED) {
			if ($this->_autoreconnect) {
				$this->log(SMARTIRC_DEBUG_CONNECTION, 'DEBUG_CONNECTION: connection error detected, will reconnect!', __FILE__, __LINE__);
				$this->reconnect();
				return ($this->_connectionerror && $this->_state() === SMARTIRC_STATE_DISCONNECTED);
			} else {
				$this->log(SMARTIRC_DEBUG_CONNECTION, 'DEBUG_CONNECTION: connection error detected, will disconnect!', __FILE__, __LINE__);
				$this->disconnect();
				return false;
			}
		}
		return true;
	}

	/**
	 * waits for a special message type and puts the answer in $result
	 *
	 * Creates a special actionhandler for that given TYPE and returns the answer.
	 * This will only receive the requested type, immediately quit and disconnect from the IRC server.
	 * Made for showing IRC statistics on your homepage, or other IRC related information.
	 *
	 * @param integer $messagetype see in the documentation 'Message Types'
	 * @return array answer from the IRC server for this $messagetype
	 * @access public
	 */
	public function listenFor($messagetype)
	{
		$listenfor = new Net_SmartIRC_listenfor();
		$this->registerActionhandler($messagetype, '.*', $listenfor, 'handler');
		$this->listen();
		$result = $listenfor->result;

		if (isset($listenfor)) {
			unset($listenfor);
		}

		return $result;
	}

	/**
	 * registers a new actionhandler and returns the assigned id
	 *
	 * Registers an actionhandler in Net_SmartIRC for calling it later.
	 * The actionhandler id is needed for unregistering the actionhandler.
	 *
	 * @see example.php
	 * @param integer $handlertype bits constants, see in this documentation Message Types
	 * @param string $regexhandler the message that has to be in the IRC message in regex syntax
	 * @param object $object a reference to the objects of the method
	 * @param string $methodname the methodname that will be called when the handler happens
	 * @return integer assigned actionhandler id
	 * @access public
	 */
	public function registerActionhandler($handlertype, $regexhandler, &$object, $methodname)
	{
		// precheck
		if (!$this->_isValidType($handlertype)) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: passed invalid handlertype to registerActionhandler()', __FILE__, __LINE__);
			return false;
		}

		$id = $this->_actionhandlerid++;
		$newactionhandler = new Net_SmartIRC_actionhandler();

		$newactionhandler->id = $id;
		$newactionhandler->type = $handlertype;
		$newactionhandler->message = $regexhandler;
		$newactionhandler->object = &$object;
		$newactionhandler->method = $methodname;

		$this->_actionhandler[] = &$newactionhandler;
		$this->log(SMARTIRC_DEBUG_ACTIONHANDLER, 'DEBUG_ACTIONHANDLER: actionhandler('.$id.') registered', __FILE__, __LINE__);
		return $id;
	}

	/**
	 * unregisters an existing actionhandler
	 *
	 * @param integer $handlertype
	 * @param string $regexhandler
	 * @param object $object
	 * @param string $methodname
	 * @return boolean
	 * @access public
	 */
	public function unregisterActionhandler($handlertype, $regexhandler, &$object, $methodname)
	{
		// precheck
		if (!$this->_isValidType($handlertype)) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: passed invalid handlertype to unregisterActionhandler()', __FILE__, __LINE__);
			return false;
		}

		$handler = &$this->_actionhandler;
		$handlercount = count($handler);

		for ($i = 0; $i < $handlercount; $i++) {
			$handlerobject = &$handler[$i];

			if ($handlerobject->type == $handlertype &&
				$handlerobject->message == $regexhandler &&
				$handlerobject->method == $methodname) {

				$id = $handlerobject->id;

				if (isset($this->_actionhandler[$i])) {
					unset($this->_actionhandler[$i]);
				}

				$this->log(SMARTIRC_DEBUG_ACTIONHANDLER, 'DEBUG_ACTIONHANDLER: actionhandler('.$id.') unregistered', __FILE__, __LINE__);
				$this->_reorderactionhandler();
				return true;
			}
		}

		$this->log(SMARTIRC_DEBUG_ACTIONHANDLER, 'DEBUG_ACTIONHANDLER: could not find actionhandler type: "'.$handlertype.'" message: "'.$regexhandler.'" method: "'.$methodname.'" from object "'.get_class($object).'" _not_ unregistered', __FILE__, __LINE__);
		return false;
	}

	/**
	 * unregisters an existing actionhandler via the id
	 *
	 * @param integer $id
	 * @return boolean
	 * @access public
	 */
	public function unregisterActionid($id)
	{
		$handler = &$this->_actionhandler;
		$handlercount = count($handler);
		for ($i = 0; $i < $handlercount; $i++) {
			$handlerobject = &$handler[$i];

			if ($handlerobject->id == $id) {
				if (isset($this->_actionhandler[$i])) {
					unset($this->_actionhandler[$i]);
				}

				$this->log(SMARTIRC_DEBUG_ACTIONHANDLER, 'DEBUG_ACTIONHANDLER: actionhandler('.$id.') unregistered', __FILE__, __LINE__);
				$this->_reorderactionhandler();
				return true;
			}
		}

		$this->log(SMARTIRC_DEBUG_ACTIONHANDLER, 'DEBUG_ACTIONHANDLER: could not find actionhandler id: '.$id.' _not_ unregistered', __FILE__, __LINE__);
		return false;
	}

	/**
	 * registers a timehandler and returns the assigned id
	 *
	 * Registers a timehandler in Net_SmartIRC, which will be called in the specified interval.
	 * The timehandler id is needed for unregistering the timehandler.
	 *
	 * @see example7.php
	 * @param integer $interval interval time in milliseconds
	 * @param object $object a reference to the objects of the method
	 * @param string $methodname the methodname that will be called when the handler happens
	 * @return integer assigned timehandler id
	 * @access public
	 */
	public function registerTimehandler($interval, &$object, $methodname)
	{
		$id = $this->_timehandlerid++;
		$newtimehandler = new Net_SmartIRC_timehandler();

		$newtimehandler->id = $id;
		$newtimehandler->interval = $interval;
		$newtimehandler->object = &$object;
		$newtimehandler->method = $methodname;
		$newtimehandler->lastmicrotimestamp = $this->_microint();

		$this->_timehandler[] = &$newtimehandler;
		$this->log(SMARTIRC_DEBUG_TIMEHANDLER, 'DEBUG_TIMEHANDLER: timehandler('.$id.') registered', __FILE__, __LINE__);

		if (($interval < $this->_mintimer) || ($this->_mintimer == false)) {
			$this->_mintimer = $interval;
		}

		return $id;
	}

	/**
	 * unregisters an existing timehandler via the id
	 *
	 * @see example7.php
	 * @param integer $id
	 * @return boolean
	 * @access public
	 */
	public function unregisterTimeid($id)
	{
		$handler = &$this->_timehandler;
		$handlercount = count($handler);
		for ($i = 0; $i < $handlercount; $i++) {
			$handlerobject = &$handler[$i];

			if ($handlerobject->id == $id) {
				if (isset($this->_timehandler[$i])) {
					unset($this->_timehandler[$i]);
				}

				$this->log(SMARTIRC_DEBUG_TIMEHANDLER, 'DEBUG_TIMEHANDLER: timehandler('.$id.') unregistered', __FILE__, __LINE__);
				$this->_reordertimehandler();
				$this->_updatemintimer();
				return true;
			}
		}

		$this->log(SMARTIRC_DEBUG_TIMEHANDLER, 'DEBUG_TIMEHANDLER: could not find timehandler id: '.$id.' _not_ unregistered', __FILE__, __LINE__);
		return false;
	}

	public function loadModule($name)
	{
		// is the module already loaded?
		if (in_array($name, $this->_modules)) {
			$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING! module with the name "'.$name.'" already loaded!', __FILE__, __LINE__);
			return false;
		}

		$filename = $this->_modulepath.'/'.$name.'.php';
		if (!file_exists($filename)) {
			$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: couldn\'t load module "'.$filename.'" file doesn\'t exist', __FILE__, __LINE__);
			return false;
		}

		$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: loading module: "'.$name.'"...', __FILE__, __LINE__);
		// pray that there is no parse error, it will kill us!
		include_once($filename);
		$classname = 'Net_SmartIRC_module_'.$name;

		if (!class_exists($classname)) {
			$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: class '.$classname.' not found in '.$filename, __FILE__, __LINE__);
			return false;
		}

		$methods = get_class_methods($classname);
		if (!in_array('__construct', $methods) && !in_array('module_init', $methods)) {
			$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: required method'.$classname.'::__construct not found, aborting...', __FILE__, __LINE__);
			return false;
		}

		if (!in_array('__destruct', $methods) && !in_array('module_exit', $methods)) {
			$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: required method'.$classname.'::__destruct not found, aborting...', __FILE__, __LINE__);
			return false;
		}

		$vars = array_keys(get_class_vars($classname));
		if (!in_array('name', $vars)) {
			$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: required variable '.$classname.'::name not found, aborting...', __FILE__, __LINE__);
			return false;
		}

		if (!in_array('description', $vars)) {
			$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: required variable '.$classname.'::description not found, aborting...', __FILE__, __LINE__);
			return false;
		}

		if (!in_array('author', $vars)) {
			$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: required variable '.$classname.'::author not found, aborting...', __FILE__, __LINE__);
			return false;
		}

		if (!in_array('license', $vars)) {
			$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: required variable '.$classname.'::license not found, aborting...', __FILE__, __LINE__);
			return false;
		}

		// looks like the module satisfies us, so instantiate it
		if (in_array('module_init', $methods)) {
			// we're using an old module_init style module
			$module = new $classname;
		} else if (func_num_args() == 1) {
			// we're using a new __construct style module, which maintains its
			// own reference to the $irc client object it's being used on
			$module = new $classname($this);
		} else {
			// we're using new style AND we have args to pass to the constructor
			if (func_num_args() == 2) {
				// only one arg, so pass it as is
				$module = new $classname($this, func_get_arg(1));
			} else {
				// multiple args, so pass them in an array
				$module = new $classname($this, array_slice(func_get_args(), 1));
			}
		}

		$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: successfully created'
			.' instance of: '.$classname, __FILE__, __LINE__
		);

		// check for deprecated init function and run it if it exists
		if (in_array('module_init', get_class_methods($classname))) {
			$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: calling '
				.$classname.'::module_init()', __FILE__, __LINE__
			);
			$module->module_init($this);
		}

		$this->_modules[$name] = &$module;

		$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: successfully loaded'
			.' module: '.$name, __FILE__, __LINE__
		);
		return true;
	}

	public function unloadModule($name)
	{
		$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: unloading module: '.$name.'...', __FILE__, __LINE__);

		$modules_keys = array_keys($this->_modules);
		$modulecount = count($modules_keys);
		for ($i = 0; $i < $modulecount; $i++) {
			$module = &$this->_modules[$modules_keys[$i]];
			$modulename = strtolower(get_class($module));

			if ($modulename == 'net_smartirc_module_'.$name) {
				if (in_array('module_exit', get_class_methods($modulename))) {
					$module->module_exit($this);
				}
				unset($this->_modules[$i]); // should call __destruct() on it
				$this->_reordermodules();
				$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: successfully'
					.' unloaded module: '.$name, __FILE__, __LINE__);
				return true;
			}
		}

		$this->log(SMARTIRC_DEBUG_MODULES, 'DEBUG_MODULES: couldn\'t unload'
			.' module: '.$name.' (it\'s not loaded!)', __FILE__, __LINE__
		);
		return false;
	}

	/**
	 * sends an IRC message
	 *
	 * Adds a message to the messagequeue, with the optional priority.
	 * $priority:
	 * SMARTIRC_CRITICAL
	 * SMARTIRC_HIGH
	 * SMARTIRC_MEDIUM
	 * SMARTIRC_LOW
	 *
	 * @param string $data
	 * @param integer $priority must be one of the priority constants
	 * @return boolean
	 * @access public
	 */
	public function send($data, $priority = SMARTIRC_MEDIUM)
	{
		return $this->_send($data, $priority);
	}

	// <private methods>
	/**
	 * changes a already used nickname to a new nickname plus 3 random digits
	 *
	 * @return void
	 * @access private
	 */
	protected function _nicknameinuse()
	{
		$newnickname = substr($this->_nick, 0, 5).rand(0, 999);
		$this->changeNick($newnickname, SMARTIRC_CRITICAL);
	}

	/**
	 * sends an IRC message
	 *
	 * Adds a message to the messagequeue, with the optional priority.
	 * $priority:
	 * SMARTIRC_CRITICAL
	 * SMARTIRC_HIGH
	 * SMARTIRC_MEDIUM
	 * SMARTIRC_LOW
	 *
	 * @param string $data
	 * @param integer $priority must be one of the priority constants
	 * @return boolean
	 * @access public
	 */
	public function _send($data, $priority = SMARTIRC_MEDIUM)
	{
		switch ($priority) {
			case SMARTIRC_CRITICAL:
				$this->_rawsend($data);
			break;
			case SMARTIRC_HIGH:
			case SMARTIRC_MEDIUM:
			case SMARTIRC_LOW:
				$this->_messagebuffer[$priority][] = $data;
			break;
			default:
				$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: message ('.$data.') with an invalid priority passed ('.$priority.'), message is ignored!', __FILE__, __LINE__);
				return false;
		}

		return true;
	}

	/**
	 * checks the buffer if there are messages to send
	 *
	 * @return void
	 * @access private
	 */
	protected function _checkbuffer()
	{
		if (!$this->_loggedin) {
			return;
		}

		static $highsent = 0;
		static $lastmicrotimestamp = 0;

		if ($lastmicrotimestamp == 0) {
			$lastmicrotimestamp = $this->_microint();
		}

		$highcount = count($this->_messagebuffer[SMARTIRC_HIGH]);
		$mediumcount = count($this->_messagebuffer[SMARTIRC_MEDIUM]);
		$lowcount = count($this->_messagebuffer[SMARTIRC_LOW]);
		$this->_messagebuffersize = $highcount+$mediumcount+$lowcount;

		// don't send them too fast
		if ($this->_microint() >= ($lastmicrotimestamp+($this->_senddelay/1000))) {
			$result = null;
			if ($highcount > 0 && $highsent <= 2) {
				$this->_rawsend(array_shift($this->_messagebuffer[SMARTIRC_HIGH]));
				$lastmicrotimestamp = $this->_microint();
				$highsent++;
			} else if ($mediumcount > 0) {
				$this->_rawsend(array_shift($this->_messagebuffer[SMARTIRC_MEDIUM]));
				$lastmicrotimestamp = $this->_microint();
				$highsent = 0;
			} else if ($lowcount > 0) {
				$this->_rawsend(array_shift($this->_messagebuffer[SMARTIRC_LOW]));
				$lastmicrotimestamp = $this->_microint();
			}
		}
	}

	/**
	 * Checks the running timers and calls the registered timehandler,
	 * when the interval is reached.
	 *
	 * @return void
	 * @access private
	 */
	protected function _checktimer()
	{
		if (!$this->_loggedin) {
			return;
		}

		// has to be count() because the array may change during the loop!
		for ($i = 0; $i < count($this->_timehandler); $i++) {
			$handlerobject = &$this->_timehandler[$i];
			$microtimestamp = $this->_microint();
			if ($microtimestamp >= ($handlerobject->lastmicrotimestamp+($handlerobject->interval/1000))) {
				$methodobject = &$handlerobject->object;
				$method = $handlerobject->method;
				$handlerobject->lastmicrotimestamp = $microtimestamp;

				if (@method_exists($methodobject, $method)) {
					$this->log(SMARTIRC_DEBUG_TIMEHANDLER, 'DEBUG_TIMEHANDLER: calling method "'.get_class($methodobject).'->'.$method.'"', __FILE__, __LINE__);
					$methodobject->$method($this);
				}
			}
		}
	}

	/**
	 * Checks if a receive or transmit timeout occured and reconnects if configured
	 *
	 * @return void
	 * @access private
	 */
	protected function _checktimeout()
	{
		if ($this->_autoreconnect == true) {
			$timestamp = time();
			if ($this->_lastrx < ($timestamp - $this->_rxtimeout)) {
				$this->log(SMARTIRC_DEBUG_CONNECTION, 'DEBUG_CONNECTION: receive timeout detected, doing reconnect...', __FILE__, __LINE__);
				$this->_delayReconnect();
				$this->reconnect();
			} else if ($this->_lasttx < ($timestamp - $this->_txtimeout)) {
				$this->log(SMARTIRC_DEBUG_CONNECTION, 'DEBUG_CONNECTION: transmit timeout detected, doing reconnect...', __FILE__, __LINE__);
				$this->_delayReconnect();
				$this->reconnect();
			}
		}
	}

	/**
	 * sends a raw message to the IRC server (don't use this!!)
	 *
	 * Use message() or send() instead.
	 *
	 * @param string $data
	 * @return boolean
	 * @access private
	 */
	protected function _rawsend($data)
	{
		if ($this->_state() == SMARTIRC_STATE_CONNECTED) {
			$this->log(SMARTIRC_DEBUG_IRCMESSAGES, 'DEBUG_IRCMESSAGES: sent: "'.$data.'"', __FILE__, __LINE__);

			if ($this->_usesockets == true) {
				$result = socket_write($this->_socket, $data.SMARTIRC_CRLF);
			} else {
				$result = fwrite($this->_socket, $data.SMARTIRC_CRLF);
			}


			if ($result === false) {
				// writing to the socket failed, means the connection is broken
				$this->_connectionerror = true;

				return false;
			} else {
				$this->_lasttx = time();
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * goes into main receive mode _once_ per call and waits for messages from the IRC server
	 *
	 * @return bool
	 * @access private
	 */
	protected function _rawreceive()
	{
		$lastpart = '';
		$rawdataar = array();

		$this->_checkbuffer();

		$timeout = $this->_selecttimeout();
		if ($this->_usesockets == true) {
			$sread = array($this->_socket);
			// this will trigger a warning when catching a signal
			$result = @socket_select($sread, $w = null, $e = null, 0, $timeout*1000);

			if ($result == 1) {
				// the socket got data to read
				$rawdata = socket_read($this->_socket, 10240);
			} else if ($result === false) {
				if (socket_last_error() == 4) {
					// we got hit with a SIGHUP signal
					$rawdata = null;
					global $bot;

					if (is_callable(array($bot, 'reload'))) {
						$bot->reload();
					}
				} else {
					// panic! panic! something went wrong!
					$this->log(SMARTIRC_DEBUG_NOTICE, 'WARNING: socket_select() returned false, something went wrong! Reason: '.socket_strerror(socket_last_error()), __FILE__, __LINE__);
					exit;
				}
			} else {
				// no data
				$rawdata = null;
			}
		} else {
			usleep($this->_receivedelay*1000);
			$rawdata = fread($this->_socket, 10240);
		}
		if ($rawdata === false) {
			// reading from the socket failed, the connection is broken
			$this->_connectionerror = true;
			return false;
		}

		$this->_checktimer();
		$this->_checktimeout();

		if ($rawdata !== null && !empty($rawdata)) {
			$this->_lastrx = time();
			$rawdata = str_replace("\r", '', $rawdata);
			$rawdata = $lastpart.$rawdata;

			$lastpart = substr($rawdata, strrpos($rawdata ,"\n")+1);
			$rawdata = substr($rawdata, 0, strrpos($rawdata ,"\n"));
			$rawdataar = explode("\n", $rawdata);
		}

		// loop through our received messages
		while (count($rawdataar) > 0) {
			$rawline = array_shift($rawdataar);
			$validmessage = false;

			$this->log(SMARTIRC_DEBUG_IRCMESSAGES, 'DEBUG_IRCMESSAGES: received: "'.$rawline.'"', __FILE__, __LINE__);

			// building our data packet
			$ircdata = new Net_SmartIRC_data();
			$ircdata->rawmessage = $rawline;
			$lineex = explode(' ', $rawline);
			$ircdata->rawmessageex = $lineex;
			$messagecode = $lineex[0];

			if (substr($rawline, 0, 1) == ':') {
				$validmessage = true;
				$line = substr($rawline, 1);
				$lineex = explode(' ', $line);

				// conform to RFC 2812
				$from = $lineex[0];
				// Fix for ZNC.
				$messagecode = (isset($lineex[1]) ? $lineex[1] : '');
				$exclamationpos = strpos($from, '!');
				$atpos = strpos($from, '@');
				$colonpos = strpos($line, ' :');
				if ($colonpos !== false) {
					// we want the exact position of ":" not beginning from the space
					$colonpos += 1;
				}
				$ircdata->nick = substr($from, 0, $exclamationpos);
				$ircdata->ident = substr($from, $exclamationpos+1, ($atpos-$exclamationpos)-1);
				$ircdata->host = substr($from, $atpos+1);
				$ircdata->type = $this->_gettype($rawline);
				$ircdata->from = $from;
				if ($colonpos !== false) {
					$ircdata->message = substr($line, $colonpos+1);
					$ircdata->messageex = explode(' ', $ircdata->message);
				}

				if ($ircdata->type & (SMARTIRC_TYPE_CHANNEL|
								SMARTIRC_TYPE_ACTION|
								SMARTIRC_TYPE_MODECHANGE|
								SMARTIRC_TYPE_TOPICCHANGE|
								SMARTIRC_TYPE_KICK|
								SMARTIRC_TYPE_PART|
								SMARTIRC_TYPE_JOIN)) {
					$ircdata->channel = $lineex[2];
				} else if ($ircdata->type & (SMARTIRC_TYPE_WHO|
									SMARTIRC_TYPE_BANLIST|
									SMARTIRC_TYPE_TOPIC|
									SMARTIRC_TYPE_CHANNELMODE)) {
					$ircdata->channel = $lineex[3];
				} else if ($ircdata->type & SMARTIRC_TYPE_NAME) {
					$ircdata->channel = $lineex[4];
				}

				if ($ircdata->channel !== null) {
					if (substr($ircdata->channel, 0, 1) == ':') {
						$ircdata->channel = substr($ircdata->channel, 1);
					}
				}

				$this->log(SMARTIRC_DEBUG_MESSAGEPARSER, 'DEBUG_MESSAGEPARSER: ircdata nick: "'.$ircdata->nick.
															'" ident: "'.$ircdata->ident.
															'" host: "'.$ircdata->host.
															'" type: "'.$ircdata->type.
															'" from: "'.$ircdata->from.
															'" channel: "'.$ircdata->channel.
															'" message: "'.$ircdata->message.
															'"', __FILE__, __LINE__);
			}

			// lets see if we have a messagehandler for it
			$this->_handlemessage($messagecode, $ircdata);

			if ($validmessage == true) {
				// now the actionhandlers are comming
				$this->_handleactionhandler($ircdata);
			}

			if (isset($ircdata)) {
				unset($ircdata);
			}
		}
		return true;
	}

	/**
	 * sends the pong for keeping alive
	 *
	 * Sends the PONG signal as reply of the PING from the IRC server.
	 *
	 * @param string $data
	 * @return void
	 * @access private
	 */
	protected function _pong($data)
	{
		$this->log(SMARTIRC_DEBUG_CONNECTION, 'DEBUG_CONNECTION: Ping? Pong!', __FILE__, __LINE__);
		$this->_send('PONG '.$data, SMARTIRC_CRITICAL);
	}

	/**
	 * returns the calculated selecttimeout value
	 *
	 * @return integer selecttimeout in microseconds
	 * @access private
	 */
	protected function _selecttimeout()
	{
		if ($this->_messagebuffersize == 0) {
			$this->_selecttimeout = null;

			if ($this->_mintimer != false) {
				$this->_calculateselecttimeout($this->_mintimer);
			}

			if ($this->_autoreconnect == true) {
				$this->_calculateselecttimeout($this->_rxtimeout*1000);
			}

			$this->_calculateselecttimeout($this->_maxtimer);
			return $this->_selecttimeout;
		} else {
			return $this->_senddelay;
		}
	}

	/**
	 * calculates the selecttimeout value
	 *
	 * @param int $microseconds
	 * @return void
	 * @access private
	 */
	protected function _calculateselecttimeout($microseconds)
	{
		if (($this->_selecttimeout > $microseconds) || $this->_selecttimeout === null) {
			$this->_selecttimeout = $microseconds;
		}
	}

	/**
	 * updates _mintimer to the smallest timer interval
	 *
	 * @return void
	 * @access private
	 */
	protected function _updatemintimer()
	{
		$timerarray = array();
		foreach ($this->_timehandler as $values) {
			$timerarray[] = $values->interval;
		}

		$result = array_multisort($timerarray, SORT_NUMERIC, SORT_ASC);
		if ($result == true && isset($timerarray[0])) {
			$this->_mintimer = $timerarray[0];
		} else {
			$this->_mintimer = false;
		}
	}

	/**
	 * reorders the actionhandler array, needed after removing one
	 *
	 * @return void
	 * @access private
	 */
	protected function _reorderactionhandler()
	{
		$orderedactionhandler = array();
		foreach ($this->_actionhandler as $value) {
			$orderedactionhandler[] = $value;
		}
		$this->_actionhandler = &$orderedactionhandler;
	}

	/**
	 * reorders the timehandler array, needed after removing one
	 *
	 * @return void
	 * @access private
	 */
	protected function _reordertimehandler()
	{
		$orderedtimehandler = array();
		foreach ($this->_timehandler as $value) {
			$orderedtimehandler[] = $value;
		}
		$this->_timehandler = &$orderedtimehandler;
	}

	/**
	 * reorders the modules array, needed after removing one
	 *
	 * @return void
	 * @access private
	 */
	protected function _reordermodules()
	{
		$orderedmodules = array();
		foreach ($this->_modules as $value) {
			$orderedmodules[] = $value;
		}
		$this->_modules = &$orderedmodules;
	}

	/**
	 * determines the messagetype of $line
	 *
	 * Analyses the type of an IRC message and returns the type.
	 *
	 * @param string $line
	 * @return integer SMARTIRC_TYPE_* constant
	 * @access private
	 */
	protected function _gettype($line)
	{
		if (preg_match('/^:[^ ]+? [0-9]{3} .+$/', $line) == 1) {
			$lineex = explode(' ', $line);
			$code = $lineex[1];

			switch ($code) {
				case SMARTIRC_RPL_WELCOME:
				case SMARTIRC_RPL_YOURHOST:
				case SMARTIRC_RPL_CREATED:
				case SMARTIRC_RPL_MYINFO:
				case SMARTIRC_RPL_BOUNCE:
					return SMARTIRC_TYPE_LOGIN;
				case SMARTIRC_RPL_LUSERCLIENT:
				case SMARTIRC_RPL_LUSEROP:
				case SMARTIRC_RPL_LUSERUNKNOWN:
				case SMARTIRC_RPL_LUSERME:
				case SMARTIRC_RPL_LUSERCHANNELS:
					return SMARTIRC_TYPE_INFO;
				case SMARTIRC_RPL_MOTDSTART:
				case SMARTIRC_RPL_MOTD:
				case SMARTIRC_RPL_ENDOFMOTD:
					return SMARTIRC_TYPE_MOTD;
				case SMARTIRC_RPL_NAMREPLY:
				case SMARTIRC_RPL_ENDOFNAMES:
					return SMARTIRC_TYPE_NAME;
				case SMARTIRC_RPL_WHOREPLY:
				case SMARTIRC_RPL_ENDOFWHO:
					return SMARTIRC_TYPE_WHO;
				case SMARTIRC_RPL_LISTSTART:
					return SMARTIRC_TYPE_NONRELEVANT;
				case SMARTIRC_RPL_LIST:
				case SMARTIRC_RPL_LISTEND:
					return SMARTIRC_TYPE_LIST;
				case SMARTIRC_RPL_BANLIST:
				case SMARTIRC_RPL_ENDOFBANLIST:
					return SMARTIRC_TYPE_BANLIST;
				case SMARTIRC_RPL_TOPIC:
					return SMARTIRC_TYPE_TOPIC;
				case SMARTIRC_RPL_WHOISUSER:
				case SMARTIRC_RPL_WHOISSERVER:
				case SMARTIRC_RPL_WHOISOPERATOR:
				case SMARTIRC_RPL_WHOISIDLE:
				case SMARTIRC_RPL_ENDOFWHOIS:
				case SMARTIRC_RPL_WHOISCHANNELS:
					return SMARTIRC_TYPE_WHOIS;
				case SMARTIRC_RPL_WHOWASUSER:
				case SMARTIRC_RPL_ENDOFWHOWAS:
					return SMARTIRC_TYPE_WHOWAS;
				case SMARTIRC_RPL_UMODEIS:
					return SMARTIRC_TYPE_USERMODE;
				case SMARTIRC_RPL_CHANNELMODEIS:
					return SMARTIRC_TYPE_CHANNELMODE;
				case SMARTIRC_ERR_NICKNAMEINUSE:
				case SMARTIRC_ERR_NOTREGISTERED:
					return SMARTIRC_TYPE_ERROR;
				default:
					$this->log(SMARTIRC_DEBUG_IRCMESSAGES, 'DEBUG_IRCMESSAGES: replycode UNKNOWN ('.$code.'): "'.$line.'"', __FILE__, __LINE__);
			}
		}

		if (preg_match('/^:.*? PRIVMSG .* :'.chr(1).'ACTION .*'.chr(1).'$/', $line) == 1) {
			return SMARTIRC_TYPE_ACTION;
		} else if (preg_match('/^:.*? PRIVMSG .* :'.chr(1).'.*'.chr(1).'$/', $line) == 1) {
			return (SMARTIRC_TYPE_CTCP_REQUEST|SMARTIRC_TYPE_CTCP);
		} else if (preg_match('/^:.*? NOTICE .* :'.chr(1).'.*'.chr(1).'$/', $line) == 1) {
			return (SMARTIRC_TYPE_CTCP_REPLY|SMARTIRC_TYPE_CTCP);
		} else if (preg_match('/^:.*? PRIVMSG (\&|\#|\+|\!).* :.*$/', $line) == 1) {
			return SMARTIRC_TYPE_CHANNEL;
		} else if (preg_match('/^:.*? PRIVMSG .*:.*$/', $line) == 1) {
			return SMARTIRC_TYPE_QUERY;
		} else if (preg_match('/^:.*? NOTICE .* :.*$/', $line) == 1) {
			return SMARTIRC_TYPE_NOTICE;
		} else if (preg_match('/^:.*? INVITE .* .*$/', $line) == 1) {
			return SMARTIRC_TYPE_INVITE;
		} else if (preg_match('/^:.*? JOIN .*$/', $line) == 1) {
			return SMARTIRC_TYPE_JOIN;
		} else if (preg_match('/^:.*? TOPIC .* :.*$/', $line) == 1) {
			return SMARTIRC_TYPE_TOPICCHANGE;
		} else if (preg_match('/^:.*? NICK .*$/', $line) == 1) {
			return SMARTIRC_TYPE_NICKCHANGE;
		} else if (preg_match('/^:.*? KICK .* .*$/', $line) == 1) {
			return SMARTIRC_TYPE_KICK;
		} else if (preg_match('/^:.*? PART .*$/', $line) == 1) {
			return SMARTIRC_TYPE_PART;
		} else if (preg_match('/^:.*? MODE .* .*$/', $line) == 1) {
			return SMARTIRC_TYPE_MODECHANGE;
		} else if (preg_match('/^:.*? QUIT :.*$/', $line) == 1) {
			return SMARTIRC_TYPE_QUIT;
		} else {
			$this->log(SMARTIRC_DEBUG_MESSAGETYPES, 'DEBUG_MESSAGETYPES: SMARTIRC_TYPE_UNKNOWN!: "'.$line.'"', __FILE__, __LINE__);
			return SMARTIRC_TYPE_UNKNOWN;
		}
	}

	/**
	 * updates the current connection state
	 *
	 * @return boolean
	 * @access private
	 */
	protected function _updatestate()
	{
		if (is_resource($this->_socket)) {
			$rtype = get_resource_type($this->_socket);
			if (($this->_socket !== false) &&
				($rtype == 'socket' || $rtype == 'Socket' || $rtype == 'stream')) {
				$this->_state = true;
				return true;
			}
		} else {
			$this->_state = false;
			$this->_loggedin = false;
		}
		return false;
	}

	/**
	 * returns the current connection state
	 *
	 * @return integer SMARTIRC_STATE_CONNECTED or SMARTIRC_STATE_DISCONNECTED
	 * @access private
	 */
	protected function _state()
	{
		$result = $this->_updatestate();

		if ($result == true) {
			return SMARTIRC_STATE_CONNECTED;
		} else {
			return SMARTIRC_STATE_DISCONNECTED;
		}
	}

	/**
	 * tries to find a messagehandler for the received message ($ircdata) and calls it
	 *
	 * @param string $messagecode
	 * @param object $ircdata
	 * @return void
	 * @access private
	 */
	protected function _handlemessage($messagecode, &$ircdata)
	{
		$found = false;
		$_methodname = $methodname = $_codetype = '';

		if (is_numeric($messagecode)) {
			if (!array_key_exists($messagecode, $this->nreplycodes)) {
				$this->log(SMARTIRC_DEBUG_MESSAGEHANDLER, 'DEBUG_MESSAGEHANDLER: ignoring unrecognized messagecode! "'.$messagecode.'"', __FILE__, __LINE__);
				$this->log(SMARTIRC_DEBUG_MESSAGEHANDLER, 'DEBUG_MESSAGEHANDLER: this IRC server ('.$this->_address.') doesn\'t conform to the RFC 2812!', __FILE__, __LINE__);
				return;
			}

			$methodname = 'event_'.strtolower($this->nreplycodes[$messagecode]);
			$_methodname = '_'.$methodname;
			$_codetype = 'by numeric';
		} else if (is_string($messagecode)) { // its not numericcode so already a name/string
			$methodname = 'event_'.strtolower($messagecode);
			$_methodname = '_'.$methodname;
			$_codetype = 'by string';
		}

		// if exists call internal method for the handling
		if (@method_exists($this, $_methodname)) {
		   $this->log(SMARTIRC_DEBUG_MESSAGEHANDLER, 'DEBUG_MESSAGEHANDLER: calling internal method "'.get_class($this).'->'.$_methodname.'" ('.$_codetype.')', __FILE__, __LINE__);
		   $this->$_methodname($ircdata);
		   $found = true;
		}

		// if exist, call user defined method for the handling
		if (@method_exists($this, $methodname)) {
		   $this->log(SMARTIRC_DEBUG_MESSAGEHANDLER, 'DEBUG_MESSAGEHANDLER: calling user defined method "'.get_class($this).'->'.$methodname.'" ('.$_codetype.')', __FILE__, __LINE__);
		   $this->$methodname($ircdata);
		   $found = true;
		}

		if ($found == false) {
			$this->log(SMARTIRC_DEBUG_MESSAGEHANDLER, 'DEBUG_MESSAGEHANDLER: no method found for "'.$messagecode.'" ('.$methodname.')', __FILE__, __LINE__);
		}
	}

	/**
	 * Strips control characters from a IRC message.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	protected function _stripControlCharacters($text) {
		$controlCodes = array(
			'/(\x03(?:\d{1,2}(?:,\d{1,2})?)?)/',    // Color code
			'/\x02/',                               // Bold
			'/\x0F/',                               // Escaped
			'/\x16/',                               // Italic
			'/\x1F/',                               // Underline
			'/\x12/'
		);
		return preg_replace($controlCodes,'',$text);
	}

	/**
	 * tries to find a actionhandler for the received message ($ircdata) and calls it
	 *
	 * @param object $ircdata
	 * @return void
	 * @access private
	 */
	protected function _handleactionhandler(&$ircdata)
	{
		$handler = &$this->_actionhandler;
		$handlercount = count($handler);
		for ($i = 0; $i < $handlercount; $i++) {
			$handlerobject = &$handler[$i];

			if (substr($handlerobject->message, 0, 1) == '/') {
				$regex = $handlerobject->message;
			} else {
				$regex = '/'.$handlerobject->message.'/';
			}

			$ircdata->message = $this->_stripControlCharacters($ircdata->message);
			if (($handlerobject->type & $ircdata->type) &&
				(preg_match($regex, $ircdata->message) == 1)) {

				$this->log(SMARTIRC_DEBUG_ACTIONHANDLER, 'DEBUG_ACTIONHANDLER: actionhandler match found for id: '.$i.' type: '.$ircdata->type.' message: "'.$ircdata->message.'" regex: "'.$regex.'"', __FILE__, __LINE__);

				$methodobject = &$handlerobject->object;
				$method = $handlerobject->method;

				if (@method_exists($methodobject, $method)) {
					$this->log(SMARTIRC_DEBUG_ACTIONHANDLER, 'DEBUG_ACTIONHANDLER: calling method "'.get_class($methodobject).'->'.$method.'"', __FILE__, __LINE__);
					$methodobject->$method($this, $ircdata);
				} else {
					$this->log(SMARTIRC_DEBUG_ACTIONHANDLER, 'DEBUG_ACTIONHANDLER: method doesn\'t exist! "'.get_class($methodobject).'->'.$method.'"', __FILE__, __LINE__);
				}
			}
		}
	}

	/**
	 * Delay reconnect
	 *
	 * @return void
	 * @access private
	 */
	protected function _delayReconnect()
	{
		if ($this->_reconnectdelay > 0) {
			$this->log(SMARTIRC_DEBUG_CONNECTION, 'DEBUG_CONNECTION: delaying reconnect for '.$this->_reconnectdelay.' ms', __FILE__, __LINE__);
			usleep($this->_reconnectdelay * 1000);
		}
	}

	/**
	 * getting current microtime, needed for benchmarks
	 *
	 * @return float
	 * @access private
	 */
	protected function _microint()
	{
		$tmp = microtime();
		$parts = explode(' ', $tmp);
		$floattime = (float)$parts[0] + (float)$parts[1];
		return $floattime;
	}

	/**
	 * adds an user to the channelobject or updates his info
	 *
	 * @param object $channel
	 * @param object $newuser
	 * @return void
	 * @access private
	 */
	protected function _adduser(&$channel, &$newuser)
	{
		$lowerednick = strtolower($newuser->nick);
		if ($this->isJoined($channel->name, $newuser->nick)) {
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: updating user: '.$newuser->nick.' on channel: '.$channel->name, __FILE__, __LINE__);

			// lets update the existing user
			$currentuser = &$channel->users[$lowerednick];

			if ($newuser->ident !== null) {
				$currentuser->ident = $newuser->ident;
			}
			if ($newuser->host !== null) {
				$currentuser->host = $newuser->host;
			}
			if ($newuser->realname !== null) {
				$currentuser->realname = $newuser->realname;
			}
			if ($newuser->ircop !== null) {
				$currentuser->ircop = $newuser->ircop;
			}
			if ($newuser->founder !== null) {
				$currentuser->founder = $newuser->founder;
			}
			if ($newuser->admin !== null) {
				$currentuser->admin = $newuser->admin;
			}
			if ($newuser->op !== null) {
				$currentuser->op = $newuser->op;
			}
			if ($newuser->hop !== null) {
				$currentuser->hop = $newuser->hop;
			}
			if ($newuser->voice !== null) {
				$currentuser->voice = $newuser->voice;
			}
			if ($newuser->away !== null) {
				$currentuser->away = $newuser->away;
			}
			if ($newuser->server !== null) {
				$currentuser->server = $newuser->server;
			}
			if ($newuser->hopcount !== null) {
				$currentuser->hopcount = $newuser->hopcount;
			}
		} else {
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: adding user: '.$newuser->nick.' to channel: '.$channel->name, __FILE__, __LINE__);

			// he is new just add the reference to him
			$channel->users[$lowerednick] = &$newuser;
		}

		$user = &$channel->users[$lowerednick];
		if ($user->founder) {
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: adding founder: '.$user->nick.' to channel: '.$channel->name, __FILE__, __LINE__);
			$channel->founders[$user->nick] = true;
		}
		if ($user->admin) {
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: adding admin: '.$user->nick.' to channel: '.$channel->name, __FILE__, __LINE__);
			$channel->admins[$user->nick] = true;
		}
		if ($user->op) {
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: adding op: '.$user->nick.' to channel: '.$channel->name, __FILE__, __LINE__);
			$channel->ops[$user->nick] = true;
		}
		if ($user->hop) {
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: adding half-op: '.$user->nick.' to channel: '.$channel->name, __FILE__, __LINE__);
			$channel->hops[$user->nick] = true;
		}
		if ($user->voice) {
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: adding voice: '.$user->nick.' to channel: '.$channel->name, __FILE__, __LINE__);
			$channel->voices[$user->nick] = true;
		}
	}

	/**
	 * removes an user from one channel or all if he quits
	 *
	 * @param object $ircdata
	 * @return void
	 * @access private
	 */
	protected function _removeuser(&$ircdata)
	{
		if ($ircdata->type & (SMARTIRC_TYPE_PART|SMARTIRC_TYPE_QUIT)) {
			$nick = $ircdata->nick;
		} else if ($ircdata->type & SMARTIRC_TYPE_KICK) {
			$nick = $ircdata->rawmessageex[3];
		} else {
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: unknown TYPE ('.$ircdata->type.') in _removeuser(), trying default', __FILE__, __LINE__);
			$nick = $ircdata->nick;
		}

		$lowerednick = strtolower($nick);

		if ($this->_nick == $nick) {
			$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: we left channel: '.$ircdata->channel.' destroying...', __FILE__, __LINE__);
			unset($this->_channels[strtolower($ircdata->channel)]);
		} else {
			if ($ircdata->type & SMARTIRC_TYPE_QUIT) {
				$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: user '.$nick.' quit, removing him from all channels', __FILE__, __LINE__);
				// remove the user from all channels
				$channelkeys = array_keys($this->_channels);
				foreach ($channelkeys as $channelkey) {
					// loop through all channels
					$channel = &$this->_channels[$channelkey];
					foreach ($channel->users as $uservalue) {
						// loop through all user in this channel
						if ($nick == $uservalue->nick) {
							// found him
							// kill him
							$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: found him on channel: '.$channel->name.' destroying...', __FILE__, __LINE__);
							unset($channel->users[$lowerednick]);

							if (isset($channel->founders[$nick])) {
								// die!
								$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: removing him from founder list', __FILE__, __LINE__);
								unset($channel->founders[$nick]);
							}

							if (isset($channel->admins[$nick])) {
								// die!
								$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: removing him from admin list', __FILE__, __LINE__);
								unset($channel->admins[$nick]);
							}

							if (isset($channel->ops[$nick])) {
								// die!
								$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: removing him from op list', __FILE__, __LINE__);
								unset($channel->ops[$nick]);
							}

							if (isset($channel->hops[$nick])) {
								// die!
								$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: removing him from hop list', __FILE__, __LINE__);
								unset($channel->hops[$nick]);
							}

							if (isset($channel->voices[$nick])) {
								// die!!
								$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: removing him from voice list', __FILE__, __LINE__);
								unset($channel->voices[$nick]);
							}

							// ups this was not DukeNukem 3D
						}
					}
				}
			} else {
				$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: removing user: '.$nick.' from channel: '.$ircdata->channel, __FILE__, __LINE__);
				$channel = &$this->_channels[strtolower($ircdata->channel)];
				unset($channel->users[$lowerednick]);

				if (isset($channel->founders[$nick])) {
					$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: removing him from founder list', __FILE__, __LINE__);
					unset($channel->founders[$nick]);
				}

				if (isset($channel->admins[$nick])) {
					$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: removing him from admin list', __FILE__, __LINE__);
					unset($channel->admins[$nick]);
				}

				if (isset($channel->ops[$nick])) {
					$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: removing him from op list', __FILE__, __LINE__);
					unset($channel->ops[$nick]);
				}

				if (isset($channel->hops[$nick])) {
					$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: removing him from hop list', __FILE__, __LINE__);
					unset($channel->hops[$nick]);
				}

				if (isset($channel->voices[$nick])) {
					$this->log(SMARTIRC_DEBUG_CHANNELSYNCING, 'DEBUG_CHANNELSYNCING: removing him from voice list', __FILE__, __LINE__);
					unset($channel->voices[$nick]);
				}
			}
		}
	}

	/**
	 * checks if the passed handlertype is valid
	 *
	 * @param integer $handlertype
	 * @return boolean
	 * @access private
	 */
	protected function _isValidType($handlertype) {
		if ($handlertype & SMARTIRC_TYPE_ALL) {
			return true;
		} else {
			return false;
		}
	}

	protected function isError($object)
	{
		return (bool)(is_object($object) && (strtolower(get_class($object)) == 'net_smartirc_error'));
	}

}

// includes must be after the base class definition, required for PHP5
require_once 'SmartIRC/irccommands.php';
require_once 'SmartIRC/messagehandler.php';

class Net_SmartIRC extends Net_SmartIRC_messagehandler
{
	// empty
}

/**
 * @access public
 */
class Net_SmartIRC_data
{
	/**
	 * @var string
	 * @access public
	 */
	var $from;

	/**
	 * @var string
	 * @access public
	 */
	var $nick;

	/**
	 * @var string
	 * @access public
	 */
	var $ident;

	/**
	 * @var string
	 * @access public
	 */
	var $host;

	/**
	 * @var string
	 * @access public
	 */
	var $channel;

	/**
	 * @var string
	 * @access public
	 */
	var $message;

	/**
	 * @var array
	 * @access public
	 */
	var $messageex = array();

	/**
	 * @var integer
	 * @access public
	 */
	var $type;

	/**
	 * @var string
	 * @access public
	 */
	var $rawmessage;

	/**
	 * @var array
	 * @access public
	 */
	var $rawmessageex = array();
}

/**
 * @access public
 */
class Net_SmartIRC_actionhandler
{
	/**
	 * @var integer
	 * @access public
	 */
	var $id;

	/**
	 * @var integer
	 * @access public
	 */
	var $type;

	/**
	 * @var string
	 * @access public
	 */
	var $message;

	/**
	 * @var object
	 * @access public
	 */
	var $object;

	/**
	 * @var string
	 * @access public
	 */
	var $method;
}

/**
 * @access public
 */
class Net_SmartIRC_timehandler
{
	/**
	 * @var integer
	 * @access public
	 */
	var $id;

	/**
	 * @var integer
	 * @access public
	 */
	var $interval;

	/**
	 * @var integer
	 * @access public
	 */
	var $lastmicrotimestamp;

	/**
	 * @var object
	 * @access public
	 */
	var $object;

	/**
	 * @var string
	 * @access public
	 */
	var $method;
}

/**
 * @access public
 */
class Net_SmartIRC_channel
{
	/**
	 * @var string
	 * @access public
	 */
	var $name;

	/**
	 * @var string
	 * @access public
	 */
	var $key;

	/**
	 * @var array
	 * @access public
	 */
	var $users = array();

	/**
	 * @var array
	 * @access public
	 */
	var $founders = array();

	/**
	 * @var array
	 * @access public
	 */
	var $admins = array();

	/**
	 * @var array
	 * @access public
	 */
	var $ops = array();

	/**
	 * @var array
	 * @access public
	 */
	var $hops = array();

	/**
	 * @var array
	 * @access public
	 */
	var $voices = array();

	/**
	 * @var array
	 * @access public
	 */
	var $bans = array();

	/**
	 * @var string
	 * @access public
	 */
	var $topic;

	/**
	 * @var string
	 * @access public
	 */
	var $user_limit = false;

	/**
	 * @var string
	 * @access public
	 */
	var $mode;

	/**
	 * @var integer
	 * @access public
	 */
	var $synctime_start = 0;

	/**
	 * @var integer
	 * @access public
	 */
	var $synctime_stop = 0;

	/**
	 * @var integer
	 * @access public
	 */
	var $synctime;
}

/**
 * @access public
 */
class Net_SmartIRC_user
{
	/**
	 * @var string
	 * @access public
	 */
	var $nick;

	/**
	 * @var string
	 * @access public
	 */
	var $ident;

	/**
	 * @var string
	 * @access public
	 */
	var $host;

	/**
	 * @var string
	 * @access public
	 */
	var $realname;

	/**
	 * @var boolean
	 * @access public
	 */
	var $ircop;

	/**
	 * @var boolean
	 * @access public
	 */
	var $away;

	/**
	 * @var string
	 * @access public
	 */
	var $server;

	/**
	 * @var integer
	 * @access public
	 */
	var $hopcount;
}

/**
 * @access public
 */
class Net_SmartIRC_channeluser extends Net_SmartIRC_user
{
	/**
	 * @var boolean
	 * @access public
	 */
	var $founder;

	/**
	 * @var boolean
	 * @access public
	 */
	var $admin;

	/**
	 * @var boolean
	 * @access public
	 */
	var $op;

	/**
	 * @var boolean
	 * @access public
	 */
	var $hop;

	/**
	 * @var boolean
	 * @access public
	 */
	var $voice;
}

/**
 * @access public
 */
class Net_SmartIRC_ircuser extends Net_SmartIRC_user
{
	/**
	 * @var array
	 * @access public
	 */
	var $joinedchannels = array();
}

/**
 * @access public
 */
class Net_SmartIRC_listenfor
{
	/**
	 * @var array
	 * @access public
	 */
	var $result = array();

	/**
	 * stores the received answer into the result array
	 *
	 * @param object $irc
	 * @param object $ircdata
	 * @return void
	 */
	function handler(&$irc, &$ircdata)
	{
		$irc->log(SMARTIRC_DEBUG_ACTIONHANDLER, 'DEBUG_ACTIONHANDLER: listenfor handler called', __FILE__, __LINE__);
		$this->result[] = $ircdata;
		$irc->disconnect(true);
	}
}

class Net_SmartIRC_Error
{
	var $error_msg;

	function __construct($message)
	{
		$this->error_msg = $message;
	}

	function getMessage()
	{
		return $this->error_msg;
	}
}