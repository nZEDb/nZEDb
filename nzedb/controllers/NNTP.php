<?php
require_once nZEDb_LIBS . 'Net_NNTP/NNTP/Client.php';
/**
 * Class for connecting to the usenet, retrieving articles and article headers,
 * decoding yEnc articles, decompressing article headers.
 * Extends PEAR's Net_NNTP_Client class, overrides some functions.
 */
class NNTP extends Net_NNTP_Client
{
	public $pdo;

	/**
	 * @var ColorCLI
	 * @access protected
	 */
	protected $_colorCLI;

	/**
	 * @var Logger
	 * @access protected
	 */
	protected $_debugging;

	/**
	 * Log/echo debug?
	 * @var bool
	 * @access protected
	 */
	protected $_debugBool;

	/**
	 * Echo to cli?
	 * @var bool
	 * @access protected
	 */
	protected $_echo;

	/**
	 * Does the server support XFeature GZip header compression?
	 * @var boolean
	 * @access protected
	 */
	protected $_compressionSupported = true;

	/**
	 * Is header compression enabled for the session?
	 * @var bool
	 * @access protected
	 */
	protected $_compressionEnabled = false;

	/**
	 * Currently selected group.
	 * @var string
	 * @access protected
	 */
	protected $_currentGroup = '';

	/**
	 * Port of the current NNTP server.
	 * @var int
	 * @access protected
	 */
	protected $_currentPort = NNTP_PORT;

	/**
	 * Address of the current NNTP server.
	 * @var string
	 * @access protected
	 */
	protected $_currentServer = NNTP_SERVER;

	/**
	 * Are we allowed to post to usenet?
	 * @var bool
	 * @access protected
	 */
	protected $_postingAllowed = false;

	/**
	 * How many times should we try to reconnect to the NNTP server?
	 * @var int
	 * @access protected
	 */
	protected $_nntpRetries;

	/**
	 * Default constructor.
	 *
	 * @param array $options Class instances and echo to CLI bool.
	 *
	 * @access public
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Echo'      => true,
			'Logger' => null,
			'Settings'  => null,
		];
		$options += $defaults;

		$this->_echo = ($options['Echo'] && nZEDb_ECHOCLI);

		$this->pdo = ($options['Settings'] instanceof \nzedb\db\Settings ? $options['Settings'] : new \nzedb\db\Settings());

		$this->_debugBool = (nZEDb_LOGGING || nZEDb_DEBUG);
		if ($this->_debugBool) {
			try {
				$this->_debugging = ($options['Logger'] instanceof \Logger ? $options['Logger'] : new \Logger(['ColorCLI' => $this->pdo->log]));
			} catch (\LoggerException $error) {
				$this->_debugBool = false;
			}
		}

		$this->_nntpRetries = ($this->pdo->getSetting('nntpretries') != '') ? (int)$this->pdo->getSetting('nntpretries') : 0 + 1;

		$this->_initiateYEncSettings();
	}

	/**
	 * Destruct.
	 * Close the NNTP connection if still connected.
	 *
	 * @access public
	 */
	public function __destruct()
	{
		$this->doQuit();
	}

	/**
	 * Connect to a usenet server.
	 *
	 * @param boolean $compression Should we attempt to enable XFeature Gzip compression on this connection?
	 * @param boolean $alternate   Use the alternate NNTP connection.
	 *
	 * @return mixed  On success = (bool)   Did we successfully connect to the usenet?
     *                On failure = (object) PEAR_Error.
	 *
	 * @access public
	 */
	public function doConnect($compression = true, $alternate = false)
	{
		if (// Don't reconnect to usenet if:
			// We are already connected to usenet. AND
			parent::_isConnected() &&
			// (Alternate is wanted, AND current server is alt,     OR    Alternate is not wanted AND current is main.)
			(($alternate && $this->_currentServer === NNTP_SERVER_A) || (!$alternate && $this->_currentServer === NNTP_SERVER))
		) {
			return true;
		} else {
			$this->doQuit();
		}

		$ret = $connected = $cError = $aError = false;

		// Set variables to connect based on if we are using the alternate provider or not.
		if (!$alternate) {
			$sslEnabled = (NNTP_SSLENABLED ? true : false);
			$this->_currentServer = NNTP_SERVER;
			$this->_currentPort = NNTP_PORT;
			$userName = NNTP_USERNAME;
			$password = NNTP_PASSWORD;
			$socketTimeout = (defined('NNTP_SOCKET_TIMEOUT') ? NNTP_SOCKET_TIMEOUT : $this->_socketTimeout);
		} else {
			$sslEnabled = (NNTP_SSLENABLED_A ? true : false);
			$this->_currentServer = NNTP_SERVER_A;
			$this->_currentPort = NNTP_PORT_A;
			$userName = NNTP_USERNAME_A;
			$password = NNTP_PASSWORD_A;
			$socketTimeout = (defined('NNTP_SOCKET_TIMEOUT_A') ? NNTP_SOCKET_TIMEOUT_A : $this->_socketTimeout);
		}

		$enc = ($sslEnabled ? ' (ssl)' : ' (non-ssl)');
		$sslEnabled = ($sslEnabled ? 'tls' : false);

		// Try to connect until we run of out tries.
		$retries = $this->_nntpRetries;
		while (true) {
			$retries--;
			$authenticated = false;

			// If we are not connected, try to connect.
			if (!$connected) {
				$ret = $this->connect($this->_currentServer, $sslEnabled, $this->_currentPort, 5, $socketTimeout);
			}

			// Check if we got an error while connecting.
			$cErr = $this->isError($ret);

			// If no error, we are connected.
			if (!$cErr) {
				// Say that we are connected so we don't retry.
				$connected = true;
				// When there is no error it returns bool if we are allowed to post or not.
				$this->_postingAllowed = $ret;
			} else {
				// Only fetch the message once.
				if (!$cError) {
					$cError = $ret->getMessage();
				}
			}

			// If error, try to connect again.
			if ($cErr && $retries > 0) {
				continue;
			}

			// If we have no more retries and could not connect, return an error.
			if ($retries === 0 && !$connected) {
				$message =
					"Cannot connect to server " .
					$this->_currentServer .
					$enc .
					': ' .
					$cError;
				if ($this->_debugBool) {
					$this->_debugging->log('NNTP', "doConnect", $message, \Logger::LOG_ERROR);
				}
				return $this->throwError($this->pdo->log->error($message));
			}

			// If we are connected, try to authenticate.
			if ($connected === true && $authenticated === false) {

				// If the username is empty it probably means the server does not require a username.
				if ($userName === '') {
					$authenticated = true;

				// Try to authenticate to usenet.
				} else {
					$ret2 = $this->authenticate($userName, $password);

					// Check if there was an error authenticating.
					$aErr = $this->isError($ret2);

					// If there was no error, then we are authenticated.
					if (!$aErr) {
						$authenticated = true;
					} elseif (!$aError) {
						$aError = $ret2->getMessage();
					}

					// If error, try to authenticate again.
					if ($aErr && $retries > 0) {
						continue;
					}

					// If we ran out of retries, return an error.
					if ($retries === 0 && $authenticated === false) {
						$message =
							"Cannot authenticate to server " .
							$this->_currentServer .
							$enc .
							' - ' .
							$userName .
							' (' . $aError . ')';
						if ($this->_debugBool) {
							$this->_debugging->log('NNTP', "doConnect", $message, \Logger::LOG_ERROR);
						}
						return $this->throwError($this->pdo->log->error($message));
					}
				}
			}

			// If we are connected and authenticated, try enabling compression if we have it enabled.
			if ($connected === true && $authenticated === true) {
				// Check if we should use compression on the connection.
				if ($compression === false || $this->pdo->getSetting('compressedheaders') == 0) {
					$this->_compressionSupported = false;
				}
				if ($this->_debugBool) {
					$this->_debugging->log('NNTP', "doConnect", "Connected to " . $this->_currentServer . '.', \Logger::LOG_INFO);
				}
				return true;
			}
			// If we reached this point and have not connected after all retries, break out of the loop.
			if ($retries === 0) {
				break;
			}

			// Sleep .4 seconds between retries.
			usleep(400000);
		}
		// If we somehow got out of the loop, return an error.
		$message = 'Unable to connect to ' . $this->_currentServer . $enc;
		if ($this->_debugBool) {
			$this->_debugging->log('NNTP', "doConnect", $message, \Logger::LOG_ERROR);
		}
		return $this->throwError($this->pdo->log->error($message));
	}

	/**
	 * Disconnect from the current NNTP server.
	 *
	 * @param  bool $force Force quit even if not connected?
	 *
	 * @return mixed On success : (bool)   Did we successfully disconnect from usenet?
	 *               On Failure : (object) PEAR_Error.
	 *
	 * @access public
	 */
	public function doQuit($force = false)
	{
		$this->_resetProperties();

		// Check if we are connected to usenet.
		if ($force === true || parent::_isConnected(false)) {
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', "doQuit", "Disconnecting from " . $this->_currentServer, \Logger::LOG_INFO);
			}
			// Disconnect from usenet.
			return parent::disconnect();
		}
		return true;
	}

	/**
	 * Reset some properties when disconnecting from usenet.
	 *
	 * @void
	 *
	 * @access protected
	 */
	protected function _resetProperties()
	{
		$this->_compressionEnabled = false;
		$this->_compressionSupported = true;
		$this->_currentGroup = '';
		$this->_postingAllowed = false;
		parent::_resetProperties();
	}

	/**
	 * Attempt to enable compression if the admin enabled the site setting.
	 * @note This can be used to enable compression if the server was connected without compression.
	 *
	 * @access public
	 */
	public function enableCompression()
	{
		if (!$this->pdo->getSetting('compressedheaders') == 1) {
			return;
		}
		$this->_enableCompression();
	}

	/**
	 * @param string $group    Name of the group to select.
	 * @param bool   $articles (optional) experimental! When true the article numbers is returned in 'articles'.
	 * @param bool   $force    Force a refresh to get updated data from the usenet server.
	 *
	 * @return mixed On success : (array)  Group information.
	 *               On failure : (object) PEAR_Error.
	 *
	 * @access public
	 */
	public function selectGroup($group, $articles = false, $force = false)
	{
		$connected = $this->_checkConnection(false);
		if ($connected !== true) {
			return $connected;
		}

		// Check if the current selected group is the same, or if we have not selected a group or if a fresh summary is wanted.
		if ($force || $this->_currentGroup !== $group || is_null($this->_selectedGroupSummary)) {
			$this->_currentGroup = $group;
			return parent::selectGroup($group, $articles);
		} else {
			return $this->_selectedGroupSummary;
		}
	}

	/**
	 * Fetch an overview of article(s) in the currently selected group.
	 *
	 * @param null $range
	 * @param bool $names
	 * @param bool $forceNames
	 *
	 * @return mixed On success : (array)  Multidimensional array with article headers.
	 *               On failure : (object) PEAR_Error.
	 *
	 * @access public
	 */
	public function getOverview($range = null, $names = true, $forceNames = true)
	{
		$connected = $this->_checkConnection();
		if ($connected !== true) {
			return $connected;
		}

		// Enabled header compression if not enabled.
		$this->_enableCompression();
		return parent::getOverview($range, $names, $forceNames);
	}

	/**
	 * Pass a XOVER command to the NNTP provider, return array of articles using the overview format as array keys.
	 *
	 * @note This is a faster implementation of getOverview.
	 *
	 * Example successful return:
	 *    array(9) {
	 *        'Number'     => string(9)  "679871775"
	 *        'Subject'    => string(18) "This is an example"
	 *        'From'       => string(19) "Example@example.com"
	 *        'Date'       => string(24) "26 Jun 2014 13:08:22 GMT"
	 *        'Message-ID' => string(57) "<part1of1.uS*yYxQvtAYt$5t&wmE%UejhjkCKXBJ!@example.local>"
	 *        'References' => string(0)  ""
	 *        'Bytes'      => string(3)  "123"
	 *        'Lines'      => string(1)  "9"
	 *        'Xref'       => string(66) "e alt.test:679871775"
	 *    }
	 *
	 * @param string $range Range of articles to get the overview for. Examples follow:
	 *                      Single article number:         "679871775"
	 *                      Range of article numbers:      "679871775-679999999"
	 *                      All newer than article number: "679871775-"
	 *                      All older than article number: "-679871775"
	 *                      Message-ID:                    "<part1of1.uS*yYxQvtAYt$5t&wmE%UejhjkCKXBJ!@example.local>"
	 *
	 * @return array|object Multi-dimensional Array of headers on success, PEAR object on failure.
	 */
	public function getXOVER($range)
	{
		// Check if we are still connected.
		$connected = $this->_checkConnection();
		if ($connected !== true) {
			return $connected;
		}

		// Enabled header compression if not enabled.
		$this->_enableCompression();

		// Send XOVER command to NNTP with wanted articles.
		$response = $this->_sendCommand('XOVER ' . $range);
		if ($this->isError($response)){
			return $response;
		}

		// Verify the NNTP server got the right command, get the headers data.
		switch ($response) {
			// 224, RFC2980: 'Overview information follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}
				break;

			default:
				return $this->_handleErrorResponse($response);
		}

		// Fetch the header overview format (for setting the array keys on the return array).
		if (!is_null($this->_overviewFormatCache) && isset($this->_overviewFormatCache['Xref'])) {
			$overview = $this->_overviewFormatCache;
		} else {
			$overview = $this->getOverviewFormat(false, true);
			if ($this->isError($overview)) {
				return $overview;
			}
			$this->_overviewFormatCache = $overview;
		}
		// Add the "Number" key.
		$overview = array_merge(['Number' => false], $overview);

		// Iterator used for selecting the header elements to insert into the overview format array.
		$iterator = 0;

		// Loop over strings of headers.
		foreach ($data as $key => $header) {

			// Split the individual headers by tab.
			$header = explode("\t", $header);

			// Make sure it's not empty.
			if ($header === false) {
				continue;
			}

			// Temp array to store the header.
			$headerArray = $overview;

			// Loop over the overview format and insert the individual header elements.
			foreach ($overview as $name => $element) {
				// Strip Xref:
				if ($element === true) {
					$header[$iterator] = substr($header[$iterator], 6);
				}
				$headerArray[$name] = $header[$iterator++];
			}
			// Add the individual header array back to the return array.
			$data[$key] = $headerArray;
			$iterator = 0;
		}
		// Return the array of headers.
		return $data;
	}

	/**
	 * Fetch valid groups.
	 *
	 * Returns a list of valid groups (that the client is permitted to select) and associated information.
	 *
	 * @param string $wildMat (optional) http://tools.ietf.org/html/rfc3977#section-4
	 *
	 * @return array|object Pear error on failure, array with groups on success.
	 * @access public
	 */
	public function getGroups($wildMat = null)
	{
		// Enabled header compression if not enabled.
		$this->_enableCompression();
		return parent::getGroups($wildMat);
	}

	/**
	 * Download multiple article bodies and string them together.
	 *
	 * @param string $groupName   The name of the group the articles are in.
	 * @param mixed  $identifiers (string) Message-ID.
	 *                            (int)    Article number.
	 *                            (array)  Article numbers or Message-ID's (can contain both in the same array)
	 * @param bool   $alternate   Use the alternate NNTP provider?
	 *
	 * @return mixed On success : (string) The article bodies.
	 *               On failure : (object) PEAR_Error.
	 *
	 * @access public
	 */
	public function getMessages($groupName, $identifiers, $alternate = false)
	{
		$connected = $this->_checkConnection();
		if ($connected !== true) {
			return $connected;
		}

		// String to hold all the bodies.
		$body = '';

		$aConnected = false;
		$nntp = ($alternate === true ? new \NNTP(['Echo' => $this->_echo, 'Settings' => $this->pdo]) : null);

		// Check if the msgIds are in an array.
		if (is_array($identifiers)) {

			$loops = $messageSize = 0;

			// Loop over the message-ID's or article numbers.
			foreach ($identifiers as $wanted) {

				/* This is to attempt to prevent string size overflow.
				 * We get the size of 1 body in bytes, we increment the loop on every loop,
				 * then we multiply the # of loops by the first size we got and check if it
				 * exceeds 1.7 billion bytes (less than 2GB to give us headroom).
				 * If we exceed, return the data.
				 * If we don't do this, these errors are fatal.
				 */
				if ((++$loops * $messageSize) >= 1700000000) {
					return $body;
				}

				// Download the body.
				$message = $this->_getMessage($groupName, $wanted);

				// Append the body to $body.
				if (!$this->isError($message)) {
					$body .= $message;

					if ($messageSize === 0) {
						$messageSize = strlen($message);
					}

				// If there is an error try the alternate provider or return the PEAR error.
				} else {
					// Check if admin has enabled alternate in site->edit.
					if ($alternate === true) {
						if ($aConnected === false) {
							// Check if the current connected server is the alternate or not.
							if ($this->_currentServer === NNTP_SERVER) {
								// It's the main so connect to the alternate.
								$aConnected = $nntp->doConnect(true, true);
							} else {
								// It's the alternate so connect to the main.
								$aConnected = $nntp->doConnect();
							}
						}
						// If we connected successfully to usenet try to download the article body.
						if ($aConnected === true) {
							$newBody = $nntp->_getMessage($groupName, $wanted);
							// Check if we got an error.
							if ($nntp->isError($newBody)) {
								if ($aConnected) {
									$nntp->doQuit();
								}
								// If we got some data, return it.
								if ($body !== '') {
									return $body;
								}
								if ($this->_debugBool) {
									$this->_debugging->log('NNTP', "getMessages", $newBody->getMessage(), \Logger::LOG_NOTICE);
								}
								// Return the error.
								return $newBody;
							}
							// Append the alternate body to the main body.
							$body .= $newBody;
						}
					} else {
						// If we got some data, return it.
						if ($body !== '') {
							return $body;
						}
						return $message;
					}
				}
			}

			// If it's a string check if it's a valid message-ID.
		} else if (is_string($identifiers) || is_numeric($identifiers)) {
			$body = $this->_getMessage($groupName, $identifiers);
			if ($alternate === true && $this->isError($body)) {
				$nntp->doConnect(true, true);
				$body = $nntp->_getMessage($groupName, $identifiers);
				$aConnected = true;
			}

			// Else return an error.
		} else {
			$message = 'Wrong Identifier type, array, int or string accepted. This type of var was passed: ' . gettype($identifiers);
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', "getMessages", $message, \Logger::LOG_WARNING);
			}
			return $this->throwError($this->pdo->log->error($message));
		}

		if ($aConnected === true) {
			$nntp->doQuit();
		}

		return $body;
	}

	/**
	 * Download a full article, the body and the header, return an array with named keys and their
	 * associated values, optionally decode the body using yEnc.
	 *
	 * @param string $groupName  The name of the group the article is in.
	 * @param mixed  $identifier (string)The message-ID of the article to download.
	 *                           (int) The article number.
	 * @param bool   $yEnc       Attempt to yEnc decode the body.
	 *
	 * @return mixed  On success : (array)  The article.
	 *                On failure : (object) PEAR_Error.
	 *
	 * @access public
	 */
	public function get_Article($groupName, $identifier, $yEnc = false)
	{
		$connected = $this->_checkConnection();
		if ($connected !== true) {
			return $connected;
		}

		// Make sure the requested group is already selected, if not select it.
		if (parent::group() !== $groupName) {
			// Select the group.
			$summary = $this->selectGroup($groupName);
			// If there was an error selecting the group, return PEAR error object.
			if ($this->isError($summary)) {
				if ($this->_debugBool) {
					$this->_debugging->log('NNTP', "get_Article", $summary->getMessage(), \Logger::LOG_NOTICE);
				}
				return $summary;
			}
		}

		// Check if it's an article number or message-ID.
		if (!is_numeric($identifier)) {
			// If it's a message-ID, check if it has the required triangular brackets.
			$identifier = $this->_formatMessageID($identifier);
		}

		// Download the article.
		$article = parent::getArticle($identifier);
		// If there was an error downloading the article, return a PEAR error object.
		if ($this->isError($article)) {
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', "get_Article", $article->getMessage(), \Logger::LOG_NOTICE);
			}
			return $article;
		}

		$ret = $article;
		// Make sure the article is an array and has more than 1 element.
		if (count($article) > 0) {
			$ret = [];
			$body = '';
			$emptyLine = false;
			foreach ($article as $line) {
				// If we found the empty line it means we are done reading the header and we will start reading the body.
				if (!$emptyLine) {
					if ($line === '') {
						$emptyLine = True;
						continue;
					}

					// Use the line type of the article as the array key (From, Subject, etc..).
					if (preg_match('/([A-Z-]+?): (.*)/i', $line, $matches)) {
						// If the line type takes more than 1 line, append the rest of the content to the same key.
						if (array_key_exists($matches[1], $ret)) {
							$ret[$matches[1]] .= $matches[2];
						} else {
							$ret[$matches[1]] = $matches[2];
						}
					}

					// Now we have the header, so get the body from the rest of the lines.
				} else {
					$body .= $line;
				}
			}
			// Finally we decode the message using yEnc.
			$ret['Message'] = ($yEnc ? $this->_decodeIgnoreYEnc($body) : $body);
		}
		return $ret;
	}

	/**
	 * Download a full article header.
	 *
	 * @param string $groupName  The name of the group the article is in.
	 * @param mixed $identifier (string) The message-ID of the article to download.
	 *                          (int)    The article number.
	 *
	 * @return mixed On success : (array)  The header.
	 *               On failure : (object) PEAR_Error.
	 *
	 * @access public
	 */
	public function get_Header($groupName, $identifier)
	{
		$connected = $this->_checkConnection();
		if ($connected !== true) {
			return $connected;
		}

		// Make sure the requested group is already selected, if not select it.
		if (parent::group() !== $groupName) {
			// Select the group.
			$summary = $this->selectGroup($groupName);
			// Return PEAR error object on failure.
			if ($this->isError($summary)) {
				if ($this->_debugBool) {
					$this->_debugging->log('NNTP', "get_Header", $summary->getMessage(), \Logger::LOG_NOTICE);
				}
				return $summary;
			}
		}

		// Check if it's an article number or message-id.
		if (!is_numeric($identifier)) {
			// Verify we have the required triangular brackets if it is a message-id.
			$identifier = $this->_formatMessageID($identifier);
		}

		// Download the header.
		$header = parent::getHeader($identifier);
		// If we failed, return PEAR error object.
		if ($this->isError($header)) {
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', "get_Header", $header->getMessage(), \Logger::LOG_NOTICE);
			}
			return $header;
		}

		$ret = $header;
		if (count($header) > 0) {
			$ret = [];
			// Use the line types of the header as array keys (From, Subject, etc).
			foreach ($header as $line) {
				if (preg_match('/([A-Z-]+?): (.*)/i', $line, $matches)) {
					// If the line type takes more than 1 line, re-use the same array key.
					if (array_key_exists($matches[1], $ret)) {
						$ret[$matches[1]] .= $matches[2];
					} else {
						$ret[$matches[1]] = $matches[2];
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Post an article to usenet.
	 *
	 * @param $groups   mixed   (array)  Groups. ie.: $groups = array('alt.test', 'alt.testing', 'free.pt');
	 *                          (string) Group.  ie.: $groups = 'alt.test';
	 * @param $subject  string  The subject.     ie.: $subject = 'Test article';
	 * @param $body     string  The message.     ie.: $message = 'This is only a test, please disregard.';
	 * @param $from     string  The poster.      ie.: $from = '<anon@anon.com>';
	 * @param $extra    string  Extra, separated by \r\n
	 *                                           ie.: $extra  = 'Organization: <nZEDb>\r\nNNTP-Posting-Host: <127.0.0.1>';
	 * @param $yEnc     bool    Encode the message with yEnc?
	 * @param $compress bool    Compress the message with GZip?
	 *
	 * @return          mixed   On success : (bool)   True.
	 *                          On failure : (object) PEAR_Error.
	 *
	 * @access public
	 */
	public function postArticle($groups, $subject, $body, $from, $yEnc = true, $compress = true, $extra = '')
	{
		if (!$this->_postingAllowed) {
			$message = 'You do not have the right to post articles on server ' . $this->_currentServer;
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', "postArticle", $message, \Logger::LOG_NOTICE);
			}
			return $this->throwError($this->pdo->log->error($message));
		}

		$connected = $this->_checkConnection();
		if ($connected !== true) {
			return $connected;
		}

		// Throw errors if subject or from are more than 510 chars.
		if (strlen($subject) > 510) {
			$message = 'Max length of subject is 510 chars.';
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', "postArticle", $message, \Logger::LOG_WARNING);
			}
			return $this->throwError($this->pdo->log->error($message));
		}

		if (strlen($from) > 510) {
			$message = 'Max length of from is 510 chars.';
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', "postArticle", $message, \Logger::LOG_WARNING);
			}
			return $this->throwError($this->pdo->log->error($message));
		}

		// Check if the group is string or array.
		if (is_array(($groups))) {
			$groups = implode(', ', $groups);
		}

		// Check if we should encode to yEnc.
		if ($yEnc) {
			$body = $this->encodeYEnc(($compress ? gzdeflate($body, 4) : $body), $subject);
		// If not yEnc, then check if the body is 510+ chars, split it at 510 chars and separate with \r\n
		} else {
			$body = $this->_splitLines($body, $compress);
		}

		// From is required by NNTP servers, but parent function mail does not require it, so format it.
		$from = 'From: ' . $from;
		// If we had extra stuff to post, format it with from.
		if ($extra != '') {
			$from = $from . "\r\n" . $extra;
		}

		return parent::mail($groups, $subject, $body, $from);
	}

	/**
	 * Restart the NNTP connection if an error occurs in the selectGroup
	 * function, if it does not restart display the error.
	 *
	 * @param NNTP   $nntp  Instance of class NNTP.
	 * @param string $group Name of the group.
	 * @param bool   $comp Use compression or not?
	 *
	 * @return mixed On success : (array)  The group summary.
	 *               On Failure : (object) PEAR_Error.
	 *
	 * @access public
	 */
	public function dataError($nntp, $group, $comp = true)
	{
		// Disconnect.
		$nntp->doQuit();
		// Try reconnecting. This uses another round of max retries.
		if ($nntp->doConnect($comp) !== true) {
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', "dataError", 'Unable to reconnect to usenet!', \Logger::LOG_NOTICE);
			}
			return $this->throwError('Unable to reconnect to usenet!');
		}

		// Try re-selecting the group.
		$data = $nntp->selectGroup($group);
		if ($this->isError($data)) {
			$message = "Code {$data->code}: {$data->message}\nSkipping group: {$group}";
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', "dataError", $message, \Logger::LOG_NOTICE);
			}

			if ($this->_echo) {
				$this->pdo->log->doEcho($this->pdo->log->error($message), true);
			}
			$nntp->doQuit();
		}
		return $data;
	}

	/**
	 * yEncodes a string and returns it.
	 *
	 * @param string $string     String to encode.
	 * @param string $filename   Name to use as the filename in the yEnc header (this does not have to be an actual file).
	 * @param int    $lineLength Line length to use (can be up to 254 characters).
	 * @param bool   $crc32      Pass True to include a CRC checksum in the trailer to allow decoders to verify data integrity.
	 *
	 * @return mixed On success: (string) yEnc encoded string.
	 *               On failure: (bool)   False.
	 *
	 * @access public
	 */
	public function encodeYEnc($string, $filename, $lineLength = 128, $crc32 = true)
	{
		// yEnc 1.3 draft doesn't allow line lengths of more than 254 bytes.
		if ($lineLength > 254) {
			$lineLength = 254;
		}

		if ($lineLength < 1) {
			$message = $lineLength . ' is not a valid line length.';
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', 'encodeYEnc', $message, \Logger::LOG_NOTICE);
			}
			return $this->throwError($message);
		}

		$encoded = '';
		$stringLength = strlen($string);
		// Encode each character of the string one at a time.
		for ($i = 0; $i < $stringLength; $i++) {
			$value = ((ord($string{$i}) + 42) % 256);

			// Escape NULL, TAB, LF, CR, space, . and = characters.
			if ($value == 0 || $value == 9 || $value == 10 || $value == 13 || $value == 32 || $value == 46 || $value == 61) {
				$encoded .= ('=' . chr(($value + 64) % 256));
			} else {
				$encoded .= chr($value);
			}
		}

		$encoded =
			'=ybegin line=' .
			$lineLength .
			' size=' .
			$stringLength .
			' name=' .
			trim($filename) .
			"\r\n" .
			trim(chunk_split($encoded, $lineLength)) .
			"\r\n=yend size=" .
			$stringLength;

		// Add a CRC32 checksum if desired.
		if ($crc32 === true) {
			$encoded .= ' crc32=' . strtolower(sprintf("%04X", crc32($string)));
		}

		return $encoded . "\r\n";
	}

	/**
	 * yDecodes an encoded string and either writes the result to a file or returns it as a string.
	 *
	 * @param string $string yEncoded string to decode.
	 *
	 * @return mixed On success: (string) The decoded string.
	 *               On failure: (object) PEAR_Error.
	 *
	 * @access public
	 */
	public function decodeYEnc($string)
	{
		$crc = '';
		// Extract the yEnc string itself.
		if (preg_match("/=ybegin.*size=([^ $]+).*\\r\\n(.*)\\r\\n=yend.*size=([^ $\\r\\n]+)(.*)/ims", $string, $encoded)) {
			if (preg_match('/crc32=([^ $\\r\\n]+)/ims', $encoded[4], $trailer)) {
				$crc = trim($trailer[1]);
			}
			$headerSize  = $encoded[1];
			$trailerSize = $encoded[3];
			$encoded     = $encoded[2];

		} else {
			return false;
		}

		// Remove line breaks from the string.
		$encoded = trim(str_replace("\r\n", '', $encoded));

		// Make sure the header and trailer file sizes match up.
		if ($headerSize != $trailerSize) {
			$message = 'Header and trailer file sizes do not match. This is a violation of the yEnc specification.';
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', 'decodeYEnc', $message, \Logger::LOG_NOTICE);
			}
			return $this->throwError($message);
		}

		// Decode.
		$decoded = '';
		$encodedLength = strlen($encoded);
		for ($chr = 0; $chr < $encodedLength; $chr++) {
			$decoded .= ($encoded[$chr] !== '=' ? chr(ord($encoded[$chr]) - 42) : chr((ord($encoded[++$chr]) - 64) - 42));
		}

		// Make sure the decoded file size is the same as the size specified in the header.
		if (strlen($decoded) != $headerSize) {
			$message = 'Header file size and actual file size do not match. The file is probably corrupt.';
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', 'decodeYEnc', $message, \Logger::LOG_NOTICE);
			}
			return $this->throwError($message);
		}

		// Check the CRC value
		if ($crc !== '' && (strtolower($crc) !== strtolower(sprintf("%04X", crc32($decoded))))) {
			$message = 'CRC32 checksums do not match. The file is probably corrupt.';
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', 'decodeYEnc', $message, \Logger::LOG_NOTICE);
			}
			return $this->throwError($message);
		}

		return $decoded;
	}

	/**
	 * Decode a string of text encoded with yEnc. Ignores all errors.
	 *
	 * @param  string $data The encoded text to decode.
	 *
	 * @return string The decoded yEnc string, or the input string, if it's not yEnc.
	 *
	 * @access protected
	 */
	protected function _decodeIgnoreYEnc(&$data)
	{
		if (preg_match('/^(=yBegin.*=yEnd[^$]*)$/ims', $data, $input)) {
			// If there user has no yyDecode path set, use PHP to decode yEnc.
			if ($this->_yyDecoderPath === false) {
				$data = '';
				$input =
					trim(
						preg_replace(
							'/\r\n/im', '',
							preg_replace(
								'/(^=yEnd.*)/im', '',
								preg_replace(
									'/(^=yPart.*\\r\\n)/im', '',
									preg_replace(
										'/(^=yBegin.*\\r\\n)/im', '',
										$input[1],
									1),
								1),
							1)
						)
					);

				$length = strlen($input);
				for ($chr = 0; $chr < $length; $chr++) {
					$data .= ($input[$chr] !== '=' ? chr(ord($input[$chr]) - 42) : chr((ord($input[++$chr]) - 64) - 42));
				}

			} else if ($this->_yEncExtension) {
				$data = simple_yenc_decode($input[1]);
			} else {
				$inFile = $this->_yEncTempInput . mt_rand(0, 999999);
				$ouFile = $this->_yEncTempOutput . mt_rand(0, 999999);
				file_put_contents($inFile, $input[1]);
				file_put_contents($ouFile, '');
				nzedb\utility\runCmd(
					"'" .
					$this->_yyDecoderPath .
					"' '" .
					$inFile .
					"' -o '" .
					$ouFile .
					"' -f -b" .
					$this->_yEncSilence
				);
				$data = file_get_contents($ouFile);
				if ($data === false) {
					$data = $this->throwError('Error getting data from yydecode.');
				}
				unlink($inFile);
				unlink($ouFile);
			}
		}
		return $data;
	}

	/**
	 * Path to yyDecoder binary.
	 * @var bool|string
	 * @access protected
	 */
	protected $_yyDecoderPath;

	/**
	 * If on unix, hide yydecode CLI output.
	 * @var string
	 * @access protected
	 */
	protected $_yEncSilence;

	/**
	 * Path to temp yEnc input storage file.
	 * @var string
	 * @access protected
	 */
	protected $_yEncTempInput;

	/**
	 * Path to temp yEnc output storage file.
	 * @var string
	 * @access protected
	 */
	protected $_yEncTempOutput;

	/**
	 * Use the simple_php_yenc_decode extension for decoding yEnc articles?
	 * @var bool
	 */
	protected $_yEncExtension = false;

	/**
	 * Check the Admin settings for yEnc and process them accordingly.
	 *
	 * @void
	 *
	 * @access protected
	 */
	protected function _initiateYEncSettings()
	{
		// Check if the user wants to use yyDecode or the simple_php_yenc_decode extension.
		$this->_yyDecoderPath  = ($this->pdo->getSetting('yydecoderpath') != '') ? (string)$this->pdo->getSetting('yydecoderpath') : false;
		if (strpos((string)$this->_yyDecoderPath, 'simple_php_yenc_decode') !== false) {
			if (extension_loaded('simple_php_yenc_decode')) {
				$this->_yEncExtension = true;
			} else {
				$this->_yyDecoderPath = false;
			}
		} else if ($this->_yyDecoderPath !== false) {

			$this->_yEncSilence    = (nzedb\utility\isWindows() ? '' : ' > /dev/null 2>&1');
			$this->_yEncTempInput  = nZEDb_TMP . 'yEnc' . DS;
			$this->_yEncTempOutput = $this->_yEncTempInput . 'output';
			$this->_yEncTempInput .= 'input';

			// Test if the user can read/write to the yEnc path.
			if (!is_file($this->_yEncTempInput)) {
				@file_put_contents($this->_yEncTempInput, 'x');
			}
			if (!is_file($this->_yEncTempInput) || !is_readable($this->_yEncTempInput) || !is_writable($this->_yEncTempInput)) {
				$this->_yyDecoderPath = false;
			}
			if (is_file($this->_yEncTempInput)) {
				@unlink($this->_yEncTempInput);
			}
		}
	}

	/**
	 * Split a string into lines of 510 chars ending with \r\n.
	 * Usenet limits lines to 512 chars, with \r\n that leaves us 510.
	 *
	 * @param string $string   The string to split.
	 * @param bool   $compress Compress the string with gzip?
	 *
	 * @return string The split string.
	 *
	 * @access protected
	 */
	protected function _splitLines($string, $compress = false)
	{
		// Check if the length is longer than 510 chars.
		if (strlen($string) > 510) {
			// If it is, split it @ 510 and terminate with \r\n.
			$string = chunk_split($string, 510, "\r\n");
		}

		// Compress the string if requested.
		return ($compress ? gzdeflate($string, 4) : $string);
	}

	/**
	 * Try to see if the NNTP server implements XFeature GZip Compression,
	 * change the compression bool object if so.
	 *
	 * @return mixed On success : (bool)   True:  The server understood and compression is enabled.
	 *                            (bool)   False: The server did not understand, compression is not enabled.
	 *               On failure : (object) PEAR_Error.
	 *
	 * @access protected
	 */
	protected function _enableCompression()
	{
		if ($this->_compressionEnabled === true) {
			return true;
		} else if ($this->_compressionSupported === false) {
			return false;
		}


		// Send this command to the usenet server.
		$response = $this->_sendCommand('XFEATURE COMPRESS GZIP');

		// Check if it's good.
		if ($this->isError($response)) {
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', "_enableCompression", $response->getMessage(), \Logger::LOG_NOTICE);
			}
			$this->_compressionSupported = false;
			return $response;
		} else if ($response !== 290) {
			$msg = "XFeature GZip Compression not supported. Consider disabling compression in site settings.";
			if ($this->_debugBool) {
				$this->_debugging->log('NNTP', "_enableCompression", $msg, \Logger::LOG_NOTICE);
			}

			if ($this->_echo) {
				$this->pdo->log->doEcho($this->pdo->log->error($msg), true);
			}
			$this->_compressionSupported = false;
			return false;
		}

		$this->_compressionEnabled = true;
		$this->_compressionSupported = true;
		return true;
	}

	/**
	 * Override PEAR NNTP's function to use our _getXFeatureTextResponse instead
	 * of their _getTextResponse function since it is incompatible at decoding
	 * headers when XFeature GZip compression is enabled server side.
	 *
	 * @return self    Our overridden function when compression is enabled.
	 *         parent  Parent function when no compression.
	 *
	 * @access protected
	 */
	protected function _getTextResponse()
	{
		if ($this->_compressionEnabled === true &&
			isset($this->_currentStatusResponse[1]) &&
			stripos($this->_currentStatusResponse[1], 'COMPRESS=GZIP') !== false) {

			return $this->_getXFeatureTextResponse();
		} else {
			return parent::_getTextResponse();
		}
	}

	/**
	 * Loop over the compressed data when XFeature GZip Compress is turned on,
	 * string the data until we find a indicator
	 * (period, carriage feed, line return ;; .\r\n), decompress the data,
	 * split the data (bunch of headers in a string) into an array, finally
	 * return the array.
	 *
	 * @return string/print Have we failed to decompress the data, was there a
	 *                 problem downloading the data, etc..

	 * @return mixed  On success : (array)  The headers.
	 *                On failure : (object) PEAR_Error.
	 *
	 * @access protected
	 */
	protected function &_getXFeatureTextResponse()
	{
		$possibleTerm = false;
		$data = null;

		while (!feof($this->_socket)) {

			// Did we find a possible ending ? (.\r\n)
			if ($possibleTerm !== false) {

				// Loop, sleeping shortly, to allow the server time to upload data, if it has any.
				for ($i = 0; $i < 3; $i++) {
					// If the socket is really empty, fGets will get stuck here, so set the socket to non blocking in case.
					stream_set_blocking($this->_socket, 0);

					// Now try to download from the socket.
					$buffer = fgets($this->_socket);

					// And set back the socket to blocking.
					stream_set_blocking($this->_socket, 1);

					// Don't sleep on last iteration.
					if (!empty($buffer)) {
						break;
					} else if ($i < 2) {
						usleep(10000);
					}
				}

				// If the buffer was really empty, then we know $possibleTerm was the real ending.
				if (empty($buffer)) {
					// Remove .\r\n from end, decompress data.
					$deComp = @gzuncompress(mb_substr($data, 0, -3, '8bit'));

					if (!empty($deComp)) {

						$bytesReceived = strlen($data);
						if ($this->_echo && $bytesReceived > 10240) {
							$this->pdo->log->doEcho(
								$this->pdo->log->primaryOver(
									'Received ' . round($bytesReceived / 1024) .
									'KB from group (' . $this->group() . ').'
								), true
							);
						}

						// Split the string of headers into an array of individual headers, then return it.
						$deComp = explode("\r\n", trim($deComp));
						return $deComp;
					} else {
						$message = 'Decompression of OVER headers failed.';
						if ($this->_debugBool) {
							$this->_debugging->log('NNTP', "_getXFeatureTextResponse", $message, \Logger::LOG_NOTICE);
						}
						$message = $this->throwError($this->pdo->log->error($message), 1000);
						return $message;
					}

				} else {
					// The buffer was not empty, so we know this was not the real ending, so reset $possibleTerm.
					$possibleTerm = false;
				}
			} else {
				// Get data from the stream.
				$buffer = fgets($this->_socket);
			}

			// If we got no data at all try one more time to pull data.
			if (empty($buffer)) {
				usleep(10000);
				$buffer = fgets($this->_socket);

				// If wet got nothing again, return error.
				if (empty($buffer)) {
					$message = 'Error fetching data from usenet server while downloading OVER headers.';
					if ($this->_debugBool) {
						$this->_debugging->log('NNTP', "_getXFeatureTextResponse", $message, \Logger::LOG_NOTICE);
					}
					$message = $this->throwError($this->pdo->log->error($message), 1000);
					return $message;
				}
			}

			// Append current buffer to rest of buffer.
			$data .= $buffer;

			// Check if we have the ending (.\r\n)
			if (substr($buffer, -3) === ".\r\n") {
				// We have a possible ending, next loop check if it is.
				$possibleTerm = true;
			}
		}

		$message = 'Unspecified error while downloading OVER headers.';
		if ($this->_debugBool) {
			$this->_debugging->log('NNTP', "_getXFeatureTextResponse", $message, \Logger::LOG_NOTICE);
		}
		$message = $this->throwError($this->pdo->log->error($message), 1000);;
		return $message;
	}

	/**
	 * Check if the Message-ID has the required opening and closing brackets.
	 *
	 * @param  string $messageID The Message-ID with or without brackets.
	 *
	 * @return string            Message-ID with brackets.
	 *
	 * @access protected
	 */
	protected function _formatMessageID($messageID)
	{
		$messageID = (string)$messageID;
		if (strlen($messageID) < 1) {
			return false;
		}

		// Check if the first char is <, if not add it.
		if ($messageID[0] !== '<') {
			$messageID = ('<' . $messageID);
		}

		// Check if the last char is >, if not add it.
		if (substr($messageID, -1) !== '>') {
			$messageID .= '>';
		}
		return $messageID;
	}

	/**
	 * Download an article body (an article without the header).
	 *
	 * @param string $groupName The name of the group the article is in.
	 * @param mixed $identifier (string) The message-ID of the article to download.
	 *                          (int)    The article number.
	 *
	 * @return mixed On success : (string) The article's body.
	 *               On failure : (object) PEAR_Error.
	 *
	 * @access protected
	 */
	protected function _getMessage($groupName, $identifier)
	{
		// Make sure the requested group is already selected, if not select it.
		if (parent::group() !== $groupName) {
			// Select the group.
			$summary = $this->selectGroup($groupName);
			// If there was an error selecting the group, return PEAR error object.
			if ($this->isError($summary)) {
				if ($this->_debugBool) {
					$this->_debugging->log('NNTP', "getMessage", $summary->getMessage(), \Logger::LOG_WARNING);
				}
				return $summary;
			}
		}

		// Check if this is an article number or message-id.
		if (!is_numeric($identifier)) {
			// It's a message-id so check if it has the triangular brackets.
			$identifier = $this->_formatMessageID($identifier);
		}

		// Tell the news server we want the body of an article.
		$response = $this->_sendCommand('BODY ' . $identifier);
		if ($this->isError($response)) {
			return $response;
		}

		$body = '';
		switch ($response) {
			// 222, RFC977: 'n <a> article retrieved - body follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_BODY_FOLLOWS:

				// Continue until connection is lost
				while (!feof($this->_socket)) {

					// Retrieve and append up to 1024 characters from the server.
					$line = fgets($this->_socket, 1024);

					// If the socket is empty/ an error occurs, false is returned.
					// Since the socket is blocking, the socket should not be empty, so it's definitely an error.
					if ($line === false) {
						return $this->throwError('Failed to read line from socket.', null);
					}

					// Check if the line terminates the text response.
					if ($line === ".\r\n") {
						if ($this->_debugBool) {
							$this->_debugging->log('NNTP',
								'getMessage', 'Fetched body for article ' . $identifier, \Logger::LOG_INFO
							);
						}
						// Attempt to yEnc decode and return the body.
						return $this->_decodeIgnoreYEnc($body);
					}

					// Check for line that starts with double period, remove one.
					if ($line[0] === '.' && $line[1] === '.') {
						$line = substr($line, 1);
					}

					// Add the line to the rest of the lines.
					$body .= $line;

				}
				return $this->throwError('End of stream! Connection lost?', null);

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Check if we are still connected. Reconnect if not.
	 *
	 * @param  bool $reSelectGroup Select back the group after connecting?
	 *
	 * @return mixed On success: (bool)   True;
	 *               On failure: (object) PEAR_Error
	 *
	 * @access protected
	 */
	protected function _checkConnection($reSelectGroup = true)
	{
		$currentGroup = $this->_currentGroup;
		// Check if we are connected.
		if (parent::_isConnected()) {
			$retVal = true;
		} else {
			switch($this->_currentServer) {
				case NNTP_SERVER:
					if (is_resource($this->_socket)) {
						$this->doQuit(true);
					}
					$retVal = $this->doConnect();
					break;
				case NNTP_SERVER_A:
					if (is_resource($this->_socket)) {
						$this->doQuit(true);
					}
					$retVal = $this->doConnect(true, true);
					break;
				default:
					$retVal = $this->throwError('Wrong server constant used in NNTP checkConnection()!');
			}
			if ($retVal === true && $reSelectGroup){
				$group = $this->selectGroup($currentGroup);
				if ($this->isError($group)) {
					$retVal = $group;
				}
			}
		}
		return $retVal;
	}

}
