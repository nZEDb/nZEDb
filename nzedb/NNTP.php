<?php

/**
 * Attempt to include PEAR's nntp class if it has not already been included.
 */
require_once nZEDb_LIBS . 'Net_NNTP/NNTP/Client.php';

/**
 * Class for connecting to the usenet, retrieving articles and article headers,
 * decoding yEnc articles, decompressing article headers.
 * Extends PEAR's Net_NNTP_Client class, overides some functions.
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
	 *
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
	 * Default constructor.
	 *
	 * @access public
	 */
	public function __construct()
	{
		$this->c = new ColorCLI();
		$this->s = new Sites();
		$this->site = $this->s->get();
		$this->nntpRetries = (!empty($this->site->nntpretries)) ? $this->site->nntpretries : 0;
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
		if ($compression === true && $this->_isConnected()) {
			return true;
		} else {
			$this->doQuit();
		}

		$enc = $ret = $ret2 = $connected = $sslEnabled = false;

		if (!$alternate) {
			$sslEnabled = (defined('NNTP_SSLENABLED') && NNTP_SSLENABLED) ? true : false;
		} else {
			$sslEnabled = (defined('NNTP_SSLENABLED_A') && NNTP_SSLENABLED_A) ? true : false;
		}

		if ($sslEnabled) {
			$enc = 'ssl';
		}

		// Try to connect until we run of out tries.
		$retries = ((int) $this->nntpRetries + 1);
		while (true) {
			$retries--;
			$authenticated = false;

			// If we are not connected, try to connect.
			if ($connected === false) {
				// Check if we want to connect to the alternate server.
				if (!$alternate) {
					$ret = $this->connect(NNTP_SERVER, $enc, NNTP_PORT, 5);
				} else {
					$ret = $this->connect(NNTP_SERVER_A, $enc, NNTP_PORT_A, 5);
				}
			}

			// Check if we got an error while connecting.
			$cErr = $this->isError($ret);

			// If no error, we are connected.
			if (!$cErr) {
				$connected = true;
			}

			// If error, try to connect again.
			if ($cErr && $retries > 0) {
				continue;
			}

			// If we have no more retries and could not connect, return an error.
			// This error message is never used, the return dends back and then dataerror takes over
			if ($retries === 0 && $connected === false) {
				return $this->throwError($this->c->error('\nCannot connect to server '
							. (!$alternate ? NNTP_SERVER : NNTP_SERVER_A)
							. (!$enc ? ' (non-ssl) ' : '(ssl) ') . ': ' . $ret->getMessage()));
			}

			// If we are connected, try to authenticate.
			if ($connected === true &&
				$authenticated === false &&
				(!$alternate ? defined('NNTP_USERNAME') : defined('NNTP_USERNAME_A'))) {

				// If the username is empty it probably means the server does not require a username.
				if ((!$alternate ? NNTP_USERNAME == '' : NNTP_USERNAME_A == '')) {
					$authenticated = true;

					// Try to authenticate to usenet.
				} else {
					if (!$alternate) {
						$ret2 = $this->authenticate(NNTP_USERNAME, NNTP_PASSWORD);
					} else {
						$ret2 = $this->authenticate(NNTP_USERNAME_A, NNTP_PASSWORD_A);
					}

					// Check if there was an error authenticating.
					$aErr = $this->isError($ret2);

					// If there was no error, then we are authenticated.
					if (!$aErr) {
						$authenticated = true;
					}

					// If error, try to authenticate again.
					if ($aErr && $retries > 0) {
						continue;
					}

					// If we ran out of retries, return an error.
					// This error message is never used, the return dends back and then dataerror takes over
					if ($retries === 0 && $authenticated === false) {
						return $this->throwError($this->c->error('\nCannot authenticate to server '
									. (!$alternate ? NNTP_SERVER : NNTP_SERVER_A)
									. (!$enc ? ' (non-ssl) ' : ' (ssl) ') . ' - '
									. (!$alternate ? NNTP_USERNAME : NNTP_USERNAME_A)
									. ' (' . $ret2->getMessage() . ')'));
					}
				}
			}

			// If we are connected and authenticated, try enabling compression if we have it enabled.
			if ($connected && $authenticated === true) {
				if ($compression === true && $this->site->compressedheaders === '1') {
					$this->_enableCompression();
				}
				return true;
			}
			// If we reached this point and have not connected after all retries, break out of the loop.
			if ($retries === 0) {
				break;
			}

			// Sleep 2 seconds between retries.
			usleep(200000);
		}
		// If we somehow got out of the loop, return an error.
		return $this->throwError($this->c->error('Unable to connect to usenet.'));
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
		// Check if we are connected to usenet.
		if (parent::_isConnected()) {
			// Disconnect from usenet.
			return parent::disconnect();
		}
		return true;
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
		return $this->_decodeYenc($body);
	}

	/**
	 * Download multiple article bodies and string them together.
	 *
	 * @param string $groupName The name of the group the articles are in.
	 * @param array string $msgIds The message-ID's of the article body's to download.
	 *
	 * @return string On success : The article bodies.
	 * @return object On failure : Pear error.
	 *
	 * @access public
	 */
	public function getMessages($groupName, $msgIds)
	{
		// String to hold all the bodies.
		$body = '';

		// Check if the msgIds are in an array.
		if (is_array($msgIds)) {

			// Loop over the message-ID's.
			foreach ($msgIds as $m) {
				// Download the body.
				$message = $this->getMessage($groupName, $m);

				// Append the body to $body.
				if (!$this->isError($message)) {
					$body = $body . $message;

					// If there is an error return the PEAR error object.
				} else {
					return $message;
				}
			}

			// If it's a string check if it's a valid message-ID.
		} else if (is_string($msgIds) && preg_match('/^<[^\s]+>$/', $msgIds)) {
			$body = $this->getMessage($groupName, $msgIds);

			// Else return an error.
		} else {
			return $this->throwError($this->c->error('NNTP->getMessages() $msgIds must be Array.'));
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
		// Make sure the requested group is already selected, if not select it.
		if (parent::group() !== $groupName) {
			// Select the group.
			$summary = parent::selectGroup($groupName);
			// If there was an error selecting the group, return PEAR error object.
			if ($this->isError($summary)) {
				return $summary;
			}
		}

		// Check if it's an article number or message-ID.
		if (!is_numeric($identifier)) {
			// If it's a message-ID, check if it has the required triangular breackets.
			$identifier = $this->formatMessageID($identifier);
		}

		// Download the article.
		$article = parent::getArticle($identifier);
		// If there was an error downloading the article, return a PEAR error object.
		if ($this->isError($article)) {
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
			$ret['Message'] = $yEnc ? $this->_decodeYenc($body) : $body;
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
		// Make sure the requested group is already selected, if not select it.
		if (parent::group() !== $groupName) {
			// Select the group.
			$summary = parent::selectGroup($groupName);
			// Return PEAR error object on failure.
			if ($this->isError($summary)) {
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

		// Throw errors if subject or from are more than 510 chars.
		if (strlen($subject) > 510) {
			return $this->throwError($this->c->error('Max length of subject is 510 chars.'));
		}

		if (strlen($from) > 510) {
			return $this->throwError($this->c->error('Max length of from is 510 chars.'));
		}

		// Check if the group is string or array.
		if (is_array(($groups))) {
			$groups = implode(', ', $groups);
		}

		// Check if we should encode to yEnc.
		if ($yEnc) {
			$yenc = new Yenc();
			$body = $yenc->encode($compress ? gzdeflate($body, 4) : $body, $subject);

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
		if ($nntp->doConnect($comp) === false) {
			return false;
		}

		// Try re-selecting the group.
		$data = $nntp->selectGroup($group);
		if ($this->isError($data)) {
			echo $this->c->error(
				"\nCode {$data->code}: {$data->message}\nSkipping group: {$group}\n");
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
		if ($this->compression === true && isset($this->_currentStatusResponse[1]) && stripos($this->_currentStatusResponse[1], 'COMPRESS=GZIP') !== false) {
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
		$data = null;

		while (!feof($this->_socket)) {
			// Reset only if decompression has not failed.
			if ($tries === 0) {
				$completed = false;
			}

			// Did we find a possible ending ? (.\r\n)
			if ($possibleTerm !== false) {

				// If the socket is really empty, fgets will get stuck here,
				// so set the socket to non blocking in case.
				stream_set_blocking($this->_socket, 0);

				// Now try to download from the socket.
				$buffer = fgets($this->_socket);

				// And set back the socket to blocking.
				stream_set_blocking($this->_socket, 1);

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
				$deComp = gzuncompress(mb_substr($data, 0, -3, '8bit'));
				// Split the string of headers into an array of individual headers, then return it.
				if (!empty($deComp)) {
					return explode("\r\n", trim($deComp));
				} else {
					// Try 5 times to decompress.
					if ($tries++ > 5) {
						return $this->throwError($this->c->error
									('Decompression Failed after 5 tries, connection closed.'), 1000);
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
				return $this->throwError($this->c->error
							('The NNTP server has returned no data.'), 1000);
			}

			// Get any socket error codes.
			$errorCode = socket_last_error();

			// Keep going if no errors.
			if ($errorCode === 0) {
				// Append buffer to final data object.
				$data .= $buffer;

				// Update total bytes received.
				$totalBytesReceived += $bytesReceived;

				// Show bytes received
				if ($totalBytesReceived > 10240 && $totalBytesReceived % 128 == 0) {
					echo $this->c->primaryOver('Receiving ' . round($totalBytesReceived / 1024) . 'KB from ' . $this->group() . "\r");
				}

				// Check if we have the ending (.\r\n)
				if ($bytesReceived > 2) {
					if (ord($buffer[$bytesReceived - 3]) == 0x2e && ord($buffer[$bytesReceived - 2]) == 0x0d && ord($buffer[$bytesReceived - 1]) == 0x0a) {
						// We found the terminator.
						if ($totalBytesReceived > 10240) {
							echo "\n";
						}

						// We have a possible ending, next loop check if it is.
						$possibleTerm = true;
						continue;
					}
				}
			} else {
				return $this->throwError($this->c->error('Socket error: ' .
							socket_strerror($errorCode)), 1000);
			}
		}
		// Throw an error if we get out of the loop.
		if (!feof($this->_socket)) {
			return $this->throwError($this->c->error(
						"Error: Could not find the end-of-file pointer on the gzip stream."), 1000);
		}

		return $this->throwError($this->c->error
					('Decompression Failed, connection closed.'), 1000);
	}

	/**
	 * Decoce a string of text encoded with yEnc.
	 *
	 * @note For usage outside of this class, please use the Yenc library.
	 *
	 * @param string $string The encoded text to decode.
	 *
	 * @return string  The decoded yEnc string, or the input, if it's not yEnc.
	 *
	 * @access protected
	 *
	 * @TODO: ? Maybe this function should be merged into the Yenc class?
	 */
	protected function _decodeYenc($string)
	{
		$ret = $string;
		if (preg_match('/^(=ybegin.*=yend[^$]*)$/ims', $string, $input)) {
			$ret = '';
			$input = trim(preg_replace('/\r\n/im', '', preg_replace('/(^=yend.*)/im', '', preg_replace('/(^=ypart.*\\r\\n)/im', '', preg_replace('/(^=ybegin.*\\r\\n)/im', '', $input[1], 1), 1), 1)));

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
	 *
	 * @access protected
	 */
	protected function _enableCompression()
	{
		// Send this command to the usenet server.
		$response = $this->_sendCommand('XFEATURE COMPRESS GZIP');
		// Check if it's good.
		if ($this->isError($response) || $response != 290) {
			return $response;
		}

		$this->compression = true;
		return true;
	}

}
