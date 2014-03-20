<?php

/**
 * Attempt to include PEAR's nntp class if it has not already been included.
 */
require_once nZEDb_LIBS . 'Net_NNTP/NNTP/Client.php';

/**
 * Class for connecting to the usenet, retrieving articles and article headers,
 * decoding yEnc articles, decompressing article headers.
 * Extends PEAR's Net_NNTP_Client class, overrides some functions.
 */
class NNTP extends Net_NNTP_Client
{

	/**
	 * Instance of class ColorCLI.
	 *
	 * @var object
	 * @access private
	 */
	private $c;

	/**
	 * Instance of class Site.
	 * @var object
	 * @access private
	 */
	private $s;

	/**
	 * Object containing site settings.
	 *
	 * @var object
	 * @access private
	 */
	private $site;

	/**
	 * Class instance of debugging.
	 *
	 * @var object
	 */
	private $debugging;

	/**
	 * Port of the current NNTP server.
	 * @var int
	 */
	private $currentPort;

	/**
	 * Address of the current NNTP server.
	 * @var string
	 */
	private $currentServer;

	/**
	 * How many times should we try to reconnect to the NNTP server?
	 *
	 * @var int
	 * @access private
	 */
	private $nntpRetries;

	/**
	 * Does the server support XFeature GZip header compression?
	 *
	 * @var boolean
	 * @access private
	 */
	private $compression = false;

	/**
	 * Are we allowed to post to usenet?
	 *
	 * @var bool
	 */
	protected $postingAllowed = false;

	/**
	 * Echo to cli?
	 * @var
	 */
	protected $echo;

	/**
	 * Default constructor.
	 *
	 * @param bool $echo Echo to cli?
	 *
	 * @access public
	 */
	public function __construct($echo = true)
	{
		$this->echo = ($echo && nZEDb_ECHOCLI);
		$this->c = new ColorCLI();
		$this->s = new Sites();
		$this->site = $this->s->get();
		$this->debugging = new Debugging("NNTP");
		$this->nntpRetries = ((!empty($this->site->nntpretries)) ? (int)$this->site->nntpretries : 0) + 1;
		$this->currentServer = NNTP_SERVER;
		$this->currentPort = NNTP_PORT;
	}

	/**
	 * Default destructor, close the NNTP connection if still connected.
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
	 * @param boolean $compression Should we attempt to enable XFeature Gzip
	 *     compression on this connection?
	 * @param boolean $alternate   Use the alternate NNTP connection.
	 *
	 * @return boolean On success = Did we successfully connect to the usenet?
	 * @return object  On failure = Pear error.
	 *
	 * @access public
	 */
	public function doConnect($compression = true, $alternate = false)
	{
		if (// Don't reconnect to usenet if:
			// We are already connected to usenet. AND
			$this->_isConnected() &&
			// (If compression is wanted and on,                    OR    Compression is not wanted and off.) AND
			(($compression && $this->compression)                   || (!$compression && !$this->compression)) &&
			// (Alternate is wanted, AND current server is alt,     OR    Alternate is not wanted AND current is main.)
			(($alternate && $this->currentServer === NNTP_SERVER_A) || (!$alternate && $this->currentServer === NNTP_SERVER))
		) {
			return true;
		} else {
			$this->doQuit();
		}

		$ret = $ret2 = $connected = $sslEnabled = $cError = $aError = false;

		// If we switched servers, reset objects.
		if (!$alternate) {
			$sslEnabled = NNTP_SSLENABLED ? true : false;
			$this->currentServer = NNTP_SERVER;
			$this->currentPort = NNTP_PORT;
			$userName = NNTP_USERNAME;
			$password = NNTP_PASSWORD;
		} else {
			$sslEnabled = NNTP_SSLENABLED_A ? true : false;
			$this->currentServer = NNTP_SERVER_A;
			$this->currentPort = NNTP_PORT_A;
			$userName = NNTP_USERNAME_A;
			$password = NNTP_PASSWORD_A;
		}

		$enc = ($sslEnabled ? ' (ssl)' : ' (non-ssl)');
		$sslEnabled = ($sslEnabled ? 'tls' : false);

		// Try to connect until we run of out tries.
		$retries = $this->nntpRetries;

		while (true) {
			$retries--;
			$authenticated = false;

			// If we are not connected, try to connect.
			if (!$connected) {
				 $ret = $this->connect($this->currentServer, $sslEnabled, $this->currentPort, 5);
			}

			// Check if we got an error while connecting.
			$cErr = $this->isError($ret);

			// If no error, we are connected.
			if (!$cErr) {
				// Say that we are connected so we don't retry.
				$connected = true;
				// When there is no error it returns bool if we are allowed to post or not.
				$this->postingAllowed = $ret;
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
					$this->currentServer .
					$enc .
					': ' .
					$cError;
				$this->debugging->start("doConnect", $message, 2);
				return $this->throwError($this->c->error($message));
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
					} else {
						if (!$aError) {
							$aError = $ret2->getMessage();
						}
					}

					// If error, try to authenticate again.
					if ($aErr && $retries > 0) {
						continue;
					}

					// If we ran out of retries, return an error.
					if ($retries === 0 && $authenticated === false) {
						$message =
							"Cannot authenticate to server " .
							$this->currentServer .
							$enc .
							' - ' .
							$userName .
							' (' .
							$aError .
							')';
						$this->debugging->start("doConnect", $message, 2);
						return $this->throwError($this->c->error($message));
					}
				}
			}

			// If we are connected and authenticated, try enabling compression if we have it enabled.
			if ($connected === true && $authenticated === true) {
				// Try to enable compression.
				if ($compression === true && $this->site->compressedheaders === '1') {
					$this->_enableCompression();
				}
				$this->debugging->start("doConnect", "Connected to " . $this->currentServer . '.', 5);
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
		$message = 'Unable to connect to ' . $this->currentServer . $enc;
		$this->debugging->start("doConnect", $message, 2);
		return $this->throwError($this->c->error($message));
	}

	/**
	 * Disconnect from the current NNTP server.
	 *
	 * @return bool   On success : Did we successfully disconnect from usenet?
	 * @return object On Failure : Pear error.
	 *
	 * @access public
	 */
	public function doQuit()
	{
		// Set this to false so we recheck next time.
		$this->compression = false;

		// Check if we are connected to usenet.
		if (parent::_isConnected()) {
			$this->debugging->start("doQuit", "Disconnecting from " . $this->currentServer, 5);
			// Disconnect from usenet.
			return parent::disconnect();
		}
		return true;
	}

	/**
	 * Fetch an overview of article(s) in the currently selected group.
	 *
	 * @param null $range
	 * @param bool $names
	 * @param bool $forceNames
	 *
	 * @return mixed
	 *
	 * @access public
	 */
	public function getOverview($range = null, $names = true, $forceNames = true)
	{
		$this->checkConnection();

		return parent::getOverview($range, $names, $forceNames);
	}

	/**
	 * Download an article body (an article without the header).
	 *
	 * @param string $groupName The name of the group the article is in.
	 * @param string/int $identifier (String)The message-ID of the article to download. or (Int) The article number.
	 *
	 * @return string On success : The article's body.
	 * @return object On failure : Pear error.
	 *
	 * @access public
	 */
	public function getMessage($groupName, $identifier)
	{
		// Make sure the requested group is already selected, if not select it.
		if (parent::group() !== $groupName) {
			// Select the group.
			$summary = parent::selectGroup($groupName);
			// If there was an error selecting the group, return PEAR error object.
			if ($this->isError($summary)) {
				$this->debugging->start("getMessage", $summary->getMessage(), 3);
				return $summary;
			}
		}

		// Check if this is an article number or message-id.
		if (!is_numeric($identifier)) {
			// It's a message-id so check if it has the triangular brackets.
			$identifier = $this->formatMessageID($identifier);
		}

		// Download the article body from usenet.
		$body = parent::getBody($identifier, true);
		// If there was an error, return the PEAR error object.
		if ($this->isError($body)) {
			return $body;
		}

		// Attempt to yEnc decode and return the body.
		return $this->_decodeYEnc($body);
	}

	/**
	 * Download multiple article bodies and string them together.
	 *
	 * @param string $groupName The name of the group the articles are in.
	 * @param array|string|int $identifiers Message-ID(string) or article number(int), or array containing M-ID's or A-Numbers.
	 * @param bool $alternate Use the alternate NNTP provider?
	 *
	 * @return string On success : The article bodies.
	 * @return object On failure : Pear error.
	 *
	 * @access public
	 */
	public function getMessages($groupName, $identifiers, $alternate=false)
	{
		$this->checkConnection();

		// String to hold all the bodies.
		$body = '';

		$aConnected = false;
		$nntp = new NNTP($this->echo);

		// Check if the msgIds are in an array.
		if (is_array($identifiers)) {

			// Loop over the message-ID's.
			foreach ($identifiers as $m) {
				// Download the body.
				$message = $this->getMessage($groupName, $m, $alternate);

				// Append the body to $body.
				if (!$this->isError($message)) {
					$body = $body . $message;

					// If there is an error return the PEAR error object.
				} else {
					if ($alternate) {
						if (!$aConnected) {
							// Check if the current connected server is the alternate or not.
							if ($this->currentServer === NNTP_SERVER) {
								// It's the main so connect to the alternate.
								$nntp->doConnect(true, true);
							} else {
								// It's the alternate so connect to the main.
								$nntp->doConnect();
							}
							$aConnected = true;
						}
						$newBody = $nntp->getMessage($groupName, $m);
						if ($nntp->isError($newBody)) {
							if ($aConnected) {
								$nntp->doQuit();
							}
							// If we got some data, return it.
							if ($body !== '') {
								return $body;
							}
							$this->debugging->start("getMessages", $newBody->getMessage(), 3);
							return $newBody;
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
			$body = $this->getMessage($groupName, $identifiers, $alternate);
			if ($alternate && $this->isError($body)) {
				$nntp->doConnect(true, true);
				$body = $nntp->getMessage($groupName, $identifiers);
				$aConnected = true;
			}

			// Else return an error.
		} else {
			$message = 'Wrong Identifier type, array, int or string accepted. This type of var was passed: ' . gettype($identifiers);
			$this->debugging->start("getMessages", $message, 3);
			return $this->throwError($this->c->error($message));
		}

		if ($aConnected) {
			$nntp->doQuit();
		}

		return $body;
	}

	/**
	 * Download a full article, the body and the header, return an array with named keys and their
	 * associated values, optionally decode the body using yEnc.
	 *
	 * @param string $groupName The name of the group the article is in.
	 * @param string/int $identifier (String)The message-ID of the article to download. or (Int) The article number.
	 * @param bool   $yEnc      Attempt to yEnc decode the body.
	 *
	 * @return array  On success : The article.
	 * @return object On failure : Pear error.
	 *
	 * @access public
	 */
	public function get_Article($groupName, $identifier, $yEnc = false)
	{
		$this->checkConnection();

		// Make sure the requested group is already selected, if not select it.
		if (parent::group() !== $groupName) {
			// Select the group.
			$summary = parent::selectGroup($groupName);
			// If there was an error selecting the group, return PEAR error object.
			if ($this->isError($summary)) {
				$this->debugging->start("get_Article", $summary->getMessage(), 3);
				return $summary;
			}
		}

		// Check if it's an article number or message-ID.
		if (!is_numeric($identifier)) {
			// If it's a message-ID, check if it has the required triangular brackets.
			$identifier = $this->formatMessageID($identifier);
		}

		// Download the article.
		$article = parent::getArticle($identifier);
		// If there was an error downloading the article, return a PEAR error object.
		if ($this->isError($article)) {
			$this->debugging->start("get_Article", $article->getMessage(), 3);
			return $article;
		}

		$ret = $article;
		// Make sure the article is an array and has more than 1 element.
		if (sizeof($article) > 0) {
			$ret = array();
			$body = '';
			$emptyLine = false;
			foreach ($article as $line) {
				// If we found the empty line it means we are done reading the header and we will start reading the body.
				if (!$emptyLine) {
					if ($line === "") {
						$emptyLine = True;
						continue;
					}

					// Use the line type of the article as the array key (From, Subject, etc..).
					if (preg_match('/([A-Z-]+?): (.*)/i', $line, $matches)) {
						// If the line type takes more than 1 line, append the rest of the content to the same key.
						if (array_key_exists($matches[1], $ret)) {
							$ret[$matches[1]] = $ret[$matches[1]] . $matches[2];
						} else {
							$ret[$matches[1]] = $matches[2];
						}
					}

					// Now we have the header, so get the body from the rest of the lines.
				} else {
					$body = $body . $line;
				}
			}
			// Finally we decode the message using yEnc.
			$ret['Message'] = $yEnc ? $this->_decodeYEnc($body) : $body;
		}
		return $ret;
	}

	/**
	 * Download a full article header.
	 *
	 * @param string $groupName The name of the group the article is in.
	 * @param string/int $identifier (String)The message-ID of the article to download. or (Int) The article number.
	 *
	 * @return array The header.
	 *
	 * @access public
	 */
	public function get_Header($groupName, $identifier)
	{
		$this->checkConnection();

		// Make sure the requested group is already selected, if not select it.
		if (parent::group() !== $groupName) {
			// Select the group.
			$summary = parent::selectGroup($groupName);
			// Return PEAR error object on failure.
			if ($this->isError($summary)) {
				$this->debugging->start("get_Header", $summary->getMessage(), 3);
				return $summary;
			}
		}

		// Check if it's an article number or message-id.
		if (!is_numeric($identifier)) {
			// Verify we have the required triangular brackets if it is a message-id.
			$identifier = $this->formatMessageID($identifier);
		}

		// Download the header.
		$header = parent::getHeader($identifier);
		// If we failed, return PEAR error object.
		if ($this->isError($header)) {
			$this->debugging->start("get_Header", $header->getMessage(), 3);
			return $header;
		}

		$ret = $header;
		if (sizeof($header) > 0) {
			$ret = array();
			// Use the line types of the header as array keys (From, Subject, etc).
			foreach ($header as $line) {
				if (preg_match('/([A-Z-]+?): (.*)/i', $line, $matches)) {
					// If the line type takes more than 1 line, re-use the same array key.
					if (array_key_exists($matches[1], $ret)) {
						$ret[$matches[1]] = $ret[$matches[1]] . $matches[2];
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
	 * @param $groups   array/string (Array) Array of groups. or (String) Single group.
	 *                                 ex.: (Array)  $groups = array('alt.test', 'alt.binaries.testing');
	 *                                 ex.: (String) $groups = 'alt.test';
	 * @param $subject  string       The subject.
	 *                                 ex.: $subject = 'Test article';
	 * @param $body     string       The message.
	 *                                 ex.: $message = 'This is only a test, please disregard.';
	 * @param $from     string       The person who is posting (must be in email format).
	 *                                 ex.: $from = '<anon@anon.com>';
	 * @param $extra    string       Extra stuff, separated by \r\n
	 *                                 ex.: $extra  = 'Organization: <nZEDb>\r\nNNTP-Posting-Host: <127.0.0.1>';
	 * @param $yEnc     bool         Encode the message with yEnc?
	 * @param $compress bool         Compress the message with GZip.
	 *
	 * @return          bool/object  True on success, Pear error on failure.
	 *
	 * @access public
	 */
	public function postArticle($groups, $subject, $body, $from, $yEnc = true, $compress = true, $extra = '')
	{
		if (!$this->postingAllowed) {
			$message = 'You do not have the right to post articles on server ' . $this->currentServer;
			$this->debugging->start("postArticle", $message, 4);
			return $this->throwError($this->c->error($message));
		}

		$this->checkConnection();

		// Throw errors if subject or from are more than 510 chars.
		if (strlen($subject) > 510) {
			$message = 'Max length of subject is 510 chars.';
			$this->debugging->start("postArticle", $message, 3);
			return $this->throwError($this->c->error($message));
		}

		if (strlen($from) > 510) {
			$message = 'Max length of from is 510 chars.';
			$this->debugging->start("postArticle", $message, 3);
			return $this->throwError($this->c->error($message));
		}

		// Check if the group is string or array.
		if (is_array(($groups))) {
			$groups = implode(', ', $groups);
		}

		// Check if we should encode to yEnc.
		if ($yEnc) {
			$y = new Yenc();
			$body = $y->encode(($compress ? gzdeflate($body, 4) : $body), $subject);

			// If not yEnc, then check if the body is 510+ chars, split it at 510 chars and separate with \r\n
		} else {
			$body = $this->splitLines($body, $compress);
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
	 * @param object $nntp  Instance of class NNTP.
	 * @param string $group Name of the group.
	 * @param boolean $comp Use compression or not?
	 *
	 * @return array   On success : The group summary.
	 * @return object  On Failure : Pear error.
	 *
	 * @access public
	 */
	public function dataError($nntp, $group, $comp = true)
	{
		// Disconnect.
		$nntp->doQuit();
		// Try reconnecting. This uses another round of max retries.
		if ($nntp->doConnect($comp) !== true) {
			return false;
		}

		// Try re-selecting the group.
		$data = $nntp->selectGroup($group);
		if ($this->isError($data)) {
			$message = "Code {$data->code}: {$data->message}\nSkipping group: {$group}";
			$this->debugging->start("dataError", $message, 3);

			if ($this->echo) {
				$this->c->doEcho($this->c->error($message), true);
			}
			$nntp->doQuit();
		}
		return $data;
	}

	/**
	 * Override PEAR NNTP's function to use our _getXFeatureTextResponse instead
	 * of their _getTextResponse function since it is incompatible at decoding
	 * headers when XFeature GZip compression is enabled server side.
	 *
	 * @note Overrides parent function.
	 * @note Function can not be protected because parent function is public.
	 *
	 * @return self    Our overridden function when compression is enabled.
	 * @return parent  Parent function when no compression.
	 *
	 * @access public
	 */
	public function _getTextResponse()
	{
		if ($this->compression === true &&
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

	 * @return array  On success : The headers.
	 * @return object On failure : Pear error.
	 *
	 * @access protected
	 */
	protected function _getXFeatureTextResponse()
	{
		$tries = $bytesReceived = $totalBytesReceived = 0;
		$completed = $possibleTerm = false;
		$data = $buffer = null;

		while (!feof($this->_socket)) {
			// Reset only if decompression has not failed.
			if ($tries === 0) {
				$completed = false;
			}

			// Did we find a possible ending ? (.\r\n)
			if ($possibleTerm !== false) {

				// If the socket is really empty, fGets will get stuck here,
				// so set the socket to non blocking in case.
				stream_set_blocking($this->_socket, 0);

				// Now try to download from the socket.
				$buffer = fgets($this->_socket);

				// And set back the socket to blocking.
				stream_set_blocking($this->_socket, 15);

				// If the buffer was really empty, then we know $possibleTerm
				// was the real ending.
				if (empty($buffer)) {
					$completed = true;

					// The buffer was not empty, so we know this was not
					// the real ending, so reset $possibleTerm.
				} else {
					$possibleTerm = false;
				}
			} else {
				// Don't try to re-download from the socket if decompression failed.
				if ($tries === 0) {
					// Get data from the stream.
					$buffer = fgets($this->_socket);
				}
			}

			// We found a ending, try to decompress the full buffer.
			if ($completed === true) {
				$deComp = @gzuncompress(mb_substr($data, 0, -3, '8bit'));
				// Split the string of headers into an array of individual headers, then return it.
				if (!empty($deComp)) {

					if ($this->echo && $totalBytesReceived > 10240) {
						$this->c->doEcho(
							$this->c->primaryOver(
								'Received ' .
								round($totalBytesReceived / 1024) .
								'KB from group (' .
								$this->group() .
								")."
							), true
						);
					}

					// Return array of headers.
					return explode("\r\n", trim($deComp));
				} else {
					// Try 5 times to decompress.
					if ($tries++ > 5) {
						$message = 'Decompression Failed after 5 tries.';
						$this->debugging->start("_getXFeatureTextResponse", $message, 2);
						return $this->throwError($this->c->error($message), 1000);
					}
					// Skip the loop to try decompressing again.
					continue;
				}
			}

			// Get byte count.
			$bytesReceived = strlen($buffer);

			// If we got no bytes at all try one more time to pull data.
			if ($bytesReceived === 0) {
				$buffer = fgets($this->_socket);
				$bytesReceived = strlen($buffer);
			}

			// If the buffer is zero it's zero, return error.
			if ($bytesReceived === 0) {
				$message = 'The NNTP server has returned no data.';
				$this->debugging->start("_getXFeatureTextResponse", $message, 2);
				return $this->throwError($this->c->error($message), 1000);
			}

			// Get any socket error codes.
			$errorCode = socket_last_error();

			// Keep going if no errors.
			if ($errorCode === 0) {
				// Append buffer to final data object.
				$data .= $buffer;

				// Update total bytes received.
				$totalBytesReceived += $bytesReceived;

				// Check if we have the ending (.\r\n)
				if ($bytesReceived > 2 &&
					ord($buffer[$bytesReceived - 3]) == 0x2e &&
					ord($buffer[$bytesReceived - 2]) == 0x0d &&
					ord($buffer[$bytesReceived - 1]) == 0x0a) {


					// We have a possible ending, next loop check if it is.
					$possibleTerm = true;
					continue;
				}
			} else {
				$message = 'Socket error: ' . socket_strerror($errorCode);
				$this->debugging->start("_getXFeatureTextResponse", $message, 2);
				return $this->throwError($this->c->error($message), 1000);
			}
		}
		// Throw an error if we get out of the loop.
		if (!feof($this->_socket)) {
			$message = "Error: Could not find the end-of-file pointer on the gzip stream.";
			$this->debugging->start("_getXFeatureTextResponse", $message, 2);
			return $this->throwError($this->c->error($message), 1000);
		}

		$message = 'Decompression Failed, connection closed.';
		$this->debugging->start("_getXFeatureTextResponse", $message, 2);
		return $this->throwError($this->c->error($message), 1000);
	}

	/**
	 * Check if we are still connected. Reconnect if not.
	 *
	 * @return bool
	 */
	protected function checkConnection()
	{
		// Check if we are connected.
		if (parent::_isConnected()) {
			return true;
		} else {
			switch($this->currentServer) {
				case NNTP_SERVER:
					return $this->doConnect();
				case NNTP_SERVER_A:
					return $this->doConnect(true, true);
				default:
					return false;
			}
		}
	}

	/**
	 * Decode a string of text encoded with yEnc.
	 *
	 * @note For usage outside of this class, please use the YEnc library.
	 *
	 * @param string $string The encoded text to decode.
	 *
	 * @return string  The decoded yEnc string, or the input, if it's not yEnc.
	 *
	 * @access protected
	 *
	 * @TODO: ? Maybe this function should be merged into the YEnc class?
	 */
	protected function _decodeYEnc($string)
	{
		$ret = $string;
		if (preg_match('/^(=yBegin.*=yEnd[^$]*)$/ims', $string, $input)) {
			$ret = '';
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

			for ($chr = 0; $chr < strlen($input); $chr++) {
				$ret .= ($input[$chr] != '=' ? chr(ord($input[$chr]) - 42) : chr((ord($input[++$chr]) - 64) - 42));
			}
		}
		return $ret;
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
	protected function formatMessageID($messageID)
	{
		// Check if the first char is <, if not add it.
		if ($messageID[0] !== '<') {
			$messageID = '<' . $messageID;
		}

		// Check if the last char is >, if not add it.
		if (substr($messageID, -1) !== '>') {
			$messageID = $messageID . '>';
		}
		return $messageID;
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
	protected function splitLines($string, $compress = false)
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
	 * @note Based on this script : http://pastebin.com/A3YypDAJ
	 *
	 * @return boolean On success : The server understood and compression is enabled.
	 * @return object  On failure : Pear error.
	 * @return int     On failure : Response code. (should be 500)
	 *
	 * @access protected
	 */
	protected function _enableCompression()
	{
		// Send this command to the usenet server.
		$response = $this->_sendCommand('XFEATURE COMPRESS GZIP');

		// Check if it's good.
		if ($this->isError($response)) {
			$this->debugging->start("_enableCompression", $response->getMessage(), 4);
			return $response;
		} else if ($response !== 290) {
			$msg = "XFeature GZip Compression not supported. Consider disabling compression in site settings.";
			$this->debugging->start("_enableCompression", $msg, 4);

			if ($this->echo) {
				$this->c->doEcho($this->c->error($msg), true);
			}
			return $response;
		}

		$this->compression = true;
		return true;
	}

	/**
	 * Extend to not get weak warnings.
	 *
	 * @param object $data Data to check for error.
	 * @param int $code Error code.
	 *
	 * @return mixed
	 */
	public function isError($data, $code = null)
	{
		return PEAR::isError($data, $code);
	}
}
