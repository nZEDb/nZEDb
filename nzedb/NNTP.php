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
class NNTP extends Net_NNTP_Client {
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
	private $nntpretries;

	/**
	 * Does the server support XFeature Gzip header compression?
	 *
	 * @var boolean
	 * @access private
	 */
	private $Compression = false;

	/**
	 * Primary color for console text output.
	 *
	 * @var string
	 * @access private
	 */
	private $primary = 'Green';

	/**
	 * Color for warnings on console text output.
	 *
	 * @var string
	 * @access private
	 */
	private $warning = 'Red';

	/**
	 * Color for headers(?) on console text output.
	 *
	 * @var string
	 * @access private
	 */
	private $header = 'Yellow';

	/**
	 * Default constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->c = new ColorCLI();
		$this->s = new Sites();
		$this->site = $this->s->get();
		$this->nntpretries =
			(!empty($this->site->nntpretries)) ? $this->site->nntpretries : 0;
	}

	/**
	 * Default destructor, close the connection the NNTP server if still connected.
	 *
	 * @access public
	 */
	public function __destruct() {
		if (parent::_isConnected()) {
			parent::disconnect();
		}
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
	public function doConnect($compression=true, $alternate=false) {
		if ($compression === true && $this->_isConnected()) {
			return true;
		} else {
			$this->doQuit();
		}

		$compressionStatus = $this->site->compressedheaders;
		$enc = $ret = $ret2 = $connected = $SSL_ENABLED = false;

		if (!$alternate) {
			$SSL_ENABLED = (defined('NNTP_SSLENABLED') && NNTP_SSLENABLED) ? true : false;
		} else {
			$SSL_ENABLED = (defined('NNTP_SSLENABLED_A') && NNTP_SSLENABLED_A) ? true : false;
		}

		if ($SSL_ENABLED) {
			$enc = 'ssl';
		}

		$retries = $this->nntpretries;
		while($retries-- >= 1) {
			$authent = false;
			if ($connected === false) {
				if (!$alternate) {
					$ret = $this->connect(NNTP_SERVER, $enc, NNTP_PORT, 5);
				} else {
					$ret = $this->connect(NNTP_SERVER_A, $enc, NNTP_PORT_A, 5);
				}
			}

			if ($retries === 0 && PEAR::isError($ret)) {
				return $this->throwError($this->c->error('Cannot connect to server '
					. (!$alternate ? NNTP_SERVER : NNTP_SERVER_A)
					. (!$enc ? ' (nonssl) ' : '(ssl) ') . ': ' . $ret->getMessage()));
			} else {
				$connected = true;
			}

			if ($connected === true && $authent === false
				&& (!$alternate ? defined('NNTP_USERNAME') : defined('NNTP_USERNAME_A'))) {

				if ((!$alternate ? NNTP_USERNAME == '' : NNTP_USERNAME_A == '')) {
					$authent = true;
				} else {
					if (!$alternate) {
						$ret2 = $this->authenticate(NNTP_USERNAME, NNTP_PASSWORD);
					} else {
						$ret2 = $this->authenticate(NNTP_USERNAME_A, NNTP_PASSWORD_A);
					}

					if ($retries === 0 && PEAR::isError($ret2)) {
						return $this->throwError($this->c->error('Cannot authenticate to server '
							. (!$alternate ? NNTP_SERVER : NNTP_SERVER_A)
							. (!$enc ? ' (nonssl) ' : ' (ssl) ') . ' - '
							. (!$alternate ? NNTP_USERNAME : NNTP_USERNAME_A)
							. ' (' . $ret2->getMessage() . ')'));
					} else {
						$authent = true;
					}
				}
			}

			if ($connected && $authent === true) {
				if ($compression === true && $compressionStatus == '1') {
					$this->_enableCompression();
				}
				return true;
			}
			usleep(200000);
		}
		return $this->throwError($this->c->error('Unable to connect to usenet.'));
	}

	/**
	 * Connect to a usenet server using alternate NNTP server info.
	 *
	 * @param boolean $compression Should we attempt to enable XFeature Gzip
	 *     compression on this connection?
	 *
	 * @return boolean On success : Did we successfully connect to the usenet?
	 * @return object  On failure = Pear error.
	 *
	 * @access public
	 */
	public function doConnect_A($compression=true) {
		return $this->doConnect($compression, true);
	}

	/**
	 * Create a connection to the NNTP server without XFeature GZip Compression.
	 *
	 * @return boolean On success : Did we successfully connect to the usenet?
	 * @return object  On failure = Pear error.
	 *
	 * @access public
	 */
	public function doConnectNC() {
		return $this->doConnect(false);
	}

	/**
	 * Disconnect from the current NNTP server.
	 *
	 * @return bool   On success : Did we successfully disconnect from usenet?
	 * @return object On Failure : Pear error.
	 *
	 * @access public
	 */
	public function doQuit() {
		return parent::disconnect();
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
	public function getMessage($groupName, $identifier) {
		// Make sure the requested group is already selected, if not select it.
		if (parent::group() !== $groupName) {
			$summary = parent::selectGroup($groupName);
			if (PEAR::isError($summary)) {
				return $summary;
			}
		}

		if (!is_numeric($identifier)) {
			$identifier = $this->formatMessageID($identifier);
		}

		$body = parent::getBody($identifier, true);
		if (PEAR::isError($body)) {
			return $body;
		}

		return $this->_decodeYenc($body);
	}

	/**
	 * Download multiple article bodies and string them together.
	 *
	 * @param string $groupname The name of the group the articles are in.
	 * @param array string $msgIds The message-ID's of the article
	 *                              body's to download.
	 *
	 * @return string On success : The article bodies.
	 * @return object On failure : Pear error.
	 *
	 * @access public
	 */
	public function getMessages($groupname, $msgIds) {
		$body = '';
		foreach ($msgIds as $m) {
			$message = $this->getMessage($groupname, $m);
			if (!PEAR::isError($message)) {
				$body = $body . $message;
			} else {
				return $message;
			}
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
	public function getArticle($groupName, $identifier, $yEnc=false) {
		// Make sure the requested group is already selected, if not select it.
		if (parent::group() !== $groupName) {
			$summary = parent::selectGroup($groupName);
			if (PEAR::isError($summary)) {
				return $summary;
			}
		}

		if (!is_numeric($identifier)) {
			$identifier = $this->formatMessageID($identifier);
		}

		$article = parent::getArticle($identifier);
		if (PEAR::isError($article)) {
			return $article;
		}

		$ret = $article;
		if (sizeof($article) > 0) {
			$ret = array();
			$body = '';
			$emptyLine = false;
			foreach ($article as $line) {
				if (!$emptyLine) {
					if ($line === "") {
						$emptyLine = True;
						continue;
					}

					if (preg_match('/([A-Z-]+?): (.*)/i', $line, $matches)) {
						if (array_key_exists($matches[1], $ret)) {
							$ret[$matches[1]] = $ret[$matches[1]] .  $matches[2];
						} else {
							$ret[$matches[1]] = $matches[2];
						}
					}

				} else {
					$body = $body . $line;
				}
			}
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
	public function getHeader($groupName, $identifier) {
		// Make sure the requested group is already selected, if not select it.
		if (parent::group() !== $groupName) {
			$summary = parent::selectGroup($groupName);
			if (PEAR::isError($summary)) {
				return $summary;
			}
		}

		if (!is_numeric($identifier)) {
			$identifier = $this->formatMessageID($identifier);
		}

		$header = parent::getHeader($identifier);
		if (PEAR::isError($header)) {
			return $header;
		}

		$ret = $header;
		if (sizeof($header) > 0) {
			$ret = array();
			foreach ($header as $line) {
				if (preg_match('/([A-Z-]+?): (.*)/i', $line, $matches)) {
					if (array_key_exists($matches[1], $ret)) {
						$ret[$matches[1]] = $ret[$matches[1]] .  $matches[2];
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
	 * @param $compress bool         Compress the message with gzip.
	 *
	 * @return          bool/object  True on success, Pear error on failure.
	 *
	 * @access public
	 */
	public function postArticle($groups, $subject, $body, $from, $yEnc=true, $compress=true, $extra='') {

		if (strlen($subject) > 510) {
			return $this->throwError($this->c->error('Max length of subject is 510 chars.'));
		}

		if (strlen($from) > 510) {
			return $this->throwError($this->c->error('Max length of from is 510 chars.'));
		}

		if (is_array(($groups))) {
			$groups = implode(', ', $groups);
		}

		if ($yEnc) {
			$yenc = new Yenc();
			$body = $yenc->encode($compress ? gzdeflate($body, 4) : $body, $subject);
		} else {
			$body = $this->splitLines($body, $compress);
		}


		$from = 'From: ' . $from;
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
	public function dataError($nntp, $group, $comp=true) {
		$nntp->doQuit();
		if ($nntp->doConnect($comp) === false) {
			return false;
		}

		$data = $nntp->selectGroup($group);
		if (PEAR::isError($data)) {
			echo $this->c->error(
			"Code {$data->code}: {$data->message}\nSkipping group: {$group}\n");
			$nntp->doQuit();
		}
		return $data;
	}

	/**
	 * Override PEAR NNTP's function to use our _getXfeatureTextResponse instead
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
	public function _getTextResponse() {
		if ($this->Compression === true
			&& isset($this->_currentStatusResponse[1])
			&& stripos($this->_currentStatusResponse[1], 'COMPRESS=GZIP') !== false) {
			return $this->_getXfeatureTextResponse();
		} else {
			return parent::_getTextResponse();
		}
	}

	/**
	 * Loop over the compressed data when XFeature Gzip Compress is turned on,
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
	protected function _getXfeatureTextResponse() {
		$tries = $bytesreceived = $totalbytesreceived = 0;
		$completed = $possibleterm = false;
		$data = null;

		while (!feof($this->_socket)) {
			// Reset only if decompression has not failed.
			if ($tries === 0) {
				$completed = false;
			}

			// Did we find a possible ending ? (.\r\n)
			if ($possibleterm !== false) {

				// If the socket is really empty, fgets will get stuck here,
				// so set the socket to non blocking in case.
				stream_set_blocking($this->_socket, 0);

				// Now try to download from the socket.
				$buffer = fgets($this->_socket);

				// And set back the socket to blocking.
				stream_set_blocking($this->_socket, 1);

				// If the buffer was really empty, then we know $possibleterm
				// was the real ending.
				if (empty($buffer)) {
					$completed = true;

				// The buffer was not empty, so we know this was not
				// the real ending, so reset $possibleterm.
				} else {
					$possibleterm = false;
				}
			} else {
				// Don't try to redownload from the socket if decompression failed.
				if ($tries === 0) {
					// Get data from the stream.
					$buffer = fgets($this->_socket);
				}
			}

			// We found a ending, try to decompress the full buffer.
			if ($completed === true) {
				$decomp = gzuncompress(mb_substr($data , 0 , -3, '8bit'));
				/* Split the string of headers into and array of
				 * individual headers, then return it.
				 */
				if (!empty($decomp)) {
					return explode("\r\n", trim($decomp));
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
			$bytesreceived = strlen($buffer);

			// If we got no bytes at all try one more time to pull data.
			if ($bytesreceived == 0) {
				$buffer = fgets($this->_socket);
				$bytesreceived = strlen($buffer);
			}

			// Get any socket error codes.
			 $errorcode = socket_last_error();

			// If the buffer is zero it's zero, return error.
			if ($bytesreceived === 0) {
				return $this->throwError($this->c->error
					('The NNTP server has returned no data.'), 1000);
			}

			// Keep going if no errors.
			if ($errorcode === 0) {
				// Append buffer to final data object.
				$data .= $buffer;

				// Update total bytes received.
				$totalbytesreceived += $bytesreceived;

				// Show bytes recieved
				if ($totalbytesreceived > 10240 && $totalbytesreceived % 128 == 0) {
					echo $this->c->setcolor($this->primary, 'Bold') . 'Receiving ' .
						round($totalbytesreceived / 1024) . 'KB from ' .
						$this->group() . ".\r" . $this->c->rsetcolor();
				}

				// Check to see if we have the magic terminator on the byte stream.
				if ($bytesreceived > 2) {
					if (ord($buffer[$bytesreceived - 3]) == 0x2e
						&& ord($buffer[$bytesreceived - 2]) == 0x0d
						&& ord($buffer[$bytesreceived - 1]) == 0x0a) {
						// We found the terminator.
						if ($totalbytesreceived > 10240) {
							echo "\n";
						}

						// We have a possible ending, next loop check if it is.
						$possibleterm = true;
						continue;
					}
				}
			} else {
				return $this->throwError('Socket error: ' .
					socket_strerror($errorcode), 1000);
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
	 * @note For usage outside of this class, please use the yenc library.
	 *
	 * @param string $yencodedvar The encoded text to decode.
	 *
	 * @return string  The decoded yEnc string, or the input, if it's not yEnc.
	 *
	 * @access protected
	 *
	 * @TODO: ? Maybe this function should be merged into the yenc class?
	 */
	protected function _decodeYenc($yencodedvar) {
		$ret = $yencodedvar;
		if (preg_match('/^(=ybegin.*=yend[^$]*)$/ims', $yencodedvar, $input)) {
			$ret = '';
			$input = trim(preg_replace('/\r\n/im', '',
							preg_replace('/(^=yend.*)/im', '',
							preg_replace('/(^=ypart.*\\r\\n)/im', '',
							preg_replace('/(^=ybegin.*\\r\\n)/im', '',
							$input[1], 1), 1), 1)));

			for ($chr = 0; $chr < strlen($input); $chr++) {
				$ret .= ($input[$chr] != '=' ? chr(ord($input[$chr]) - 42)
				: chr((ord($input[++$chr]) - 64) - 42));
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
	protected function formatMessageID($messageID) {
		if ($messageID[0] !== '<') {
			$messageID = '<' . $messageID;
		}
		if (substr($messageID, -1) !== '<') {
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
	protected function splitLines($string, $compress=false) {
		if (strlen($string) > 510) {
			$string = chunk_split($string, 510, "\r\n");
		}

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
	protected function _enableCompression() {
		$response = $this->_sendCommand('XFEATURE COMPRESS GZIP');
		if (PEAR::isError($response) || $response != 290) {
			return $response;
		}

		$this->Compression = true;
		return true;
	}
}
?>
