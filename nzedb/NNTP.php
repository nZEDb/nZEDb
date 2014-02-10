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
	 * Cache of the group name on the article/body functions.
	 *
	 * @var string
	 * @access private
	 */
	private $articlegroup;

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
		if (self::_isConnected()) {
			self::doQuit();
		}
	}

	/**
	 * Connect to a usenet server.
	 *
	 * @param boolean $compression Should we attempt to enable XFeature Gzip
	 *     compression on this connection?
	 * @param boolean $alternate   Use the alternate NNTP connection.
	 *
	 * @return boolean Did we sucesfully connect to the usenet?
	 *
	 * @access public
	 */
	public function doConnect($compression=true, $alternate=false) {
		if ($compression === true && $this->_isConnected()) {
			return true;
		} else {
			$this->doQuit();
		}

		$compressionstatus = $this->site->compressedheaders;
		unset($s, $site);
		$enc = $ret = $ret2 = $connected = $SSL_ENABLED = false;

		if (!$alternate) {
			$SSL_ENABLED = ((defined('NNTP_SSLENABLED') && NNTP_SSLENABLED) ? true : false);
		} else {
			$SSL_ENABLED = ((defined('NNTP_SSLENABLED_A') && NNTP_SSLENABLED_A) ? true : false);
		}

		if ($SSL_ENABLED) {
			$enc = 'ssl';
		}

		$retries = $this->nntpretries;
		while($retries >= 1) {
			$authent = false;
			$retries--;
			if ($connected === false) {
				if (!$alternate) {
					$ret = $this->connect(NNTP_SERVER, $enc, NNTP_PORT, 5);
				} else {
					$ret = $this->connect(NNTP_SERVER_A, $enc, NNTP_PORT_A, 5);
				}
			}

			if (PEAR::isError($ret) && $retries === 0) {
				echo $this->c->error('Cannot connect to server '
				. (!$alternate ? NNTP_SERVER : NNTP_SERVER_A)
				. (!$enc ? ' (nonssl) ' : '(ssl) ') . ': ' . $ret->getMessage());
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
					if (PEAR::isError($ret2) && $retries === 0) {
						echo $this->c->error('Cannot authenticate to server '
						. (!$alternate ? NNTP_SERVER : NNTP_SERVER_A)
						. (!$enc ? ' (nonssl) ' : ' (ssl) ') . ' - '
						. (!$alternate ? NNTP_USERNAME : NNTP_USERNAME_A)
						. ' (' . $ret2->getMessage() . ')');
					} else {
						$authent = true;
					}
				}
			}

			if ($connected && $authent === true) {
				if ($compression === true && $compressionstatus == '1') {
					$this->_enableCompression();
				}
				return true;
			}
			usleep(200000);
		}
		return false;
	}

	/**
	 * Connect to a usenet server using alternate NNTP server info.
	 *
	 * @param boolean $compression Should we attempt to enable XFeature Gzip
	 *     compression on this connection?
	 *
	 * @return boolean Did we sucesfully connect to the usenet?
	 *
	 * @access public
	 */
	public function doConnect_A($compression=true) {
		return $this->doConnect($compression, true);
	}

	/**
	 * Create a connection to the NNTP server without XFeature GZip Compression.
	 *
	 * @return boolean Did we sucesfully connect to the usenet?
	 *
	 * @access public
	 */
	public function doConnectNC() {
		return $this->doConnect(false);
	}

	/**
	 * Disconnect from the current NNTP server.
	 *
	 * @void
	 *
	 * @access public
	 */
	public function doQuit() {
		$this->disconnect();
	}

	/**
	 * Download an article body (an article without the header).
	 *
	 * @param string $groupname The name of the group the article is in.
	 * @param string $partMsgId The message-ID of the article body to download.
	 *
	 * @return boolean If we have failed to download the body or the body could
	 *                 not be decoded (yenc).
	 * @return string The article's body.
	 *
	 * @access public
	 */
	public function getMessage($groupname, $partMsgId) {
		if ($this->articlegroup != $groupname) {
			$this->articlegroup = $groupname;
			$summary = $this->selectGroup($groupname);
			if (PEAR::isError($summary)) {
				return false;
			}
		}

		$body = $this->getBody('<'.$partMsgId.'>', true);
		if (PEAR::isError($body)) {
			return false;
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
	 * @return boolean If we have failed to download the body's or they could
	 *                 not be decoded (yenc).
	 * @return string The article bodies.
	 *
	 * @access public
	 */
	public function getMessages($groupname, $msgIds) {
		$body = '';
		foreach ($msgIds as $m) {
			$message = $this->getMessage($groupname, $m);
			if ($message !== false) {
				$body = $body . $message;
			} else {
				return false;
			}
		}
		return $body;
	}

	/**
	 * Download a full article, the body and the header.
	 *
	 * @param string $groupname The name of the group the article is in.
	 * @param string $partMsgId The message-ID of the article to download.
	 *
	 * @return boolean If we have failed to download the body or the body could
	 *                 not be decoded (yenc).
	 * @return string The article's body.
	 *
	 * @access public
	 */
	public function get_Article($groupname, $partMsgId) {
		$body = $this->getArticle('<'.$partMsgId.'>', true);
		if (PEAR::isError($body)) {
			return false;
		}

		return $this->_decodeYenc($body);
	}

	/**
	 * Download multiple articles and string them together.
	 *
	 * @param string $groupname The name of the group the articles are in.
	 * @param array string $msgIds The message-ID's of the articles
	 *                             to download.
	 *
	 * @return boolean If we have failed to download the body's or they could
	 *                 not be decoded (yenc).
	 * @return string The article bodies.
	 *
	 * @access public
	 */
	public function getArticles($groupname, $msgIds) {
		$body = '';
/*      if ($this->articlegroup != $groupname) {
			$this->articlegroup = $groupname;
			$summary = $this->selectGroup($groupname);
			if (PEAR::isError($summary)) {
				return false;
			}
		}
*/
		foreach ($msgIds as $m) {
			$message = $this->get_Article($groupname, $m);
			if ($message !== false) {
				$body = $body . $message;
			} else {
				return false;
			}
		}
		return $body;
	}

	/**
	 * Restart the NNTP connection if an error occurs in the selectGroup
	 * function, if it does not restart display the error.
	 *
	 * @param object $nntp  Instance of class NNTP.
	 * @param string $group Name of the group.
	 * @param boolean $comp Use compression or not?
	 *
	 * @return boolean Have we failed rconnecting to usenet?
	 * @return array(?) The group data.
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
			return false;
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
	 * @return function Our function when compression is enabled, or parent
	 *                  function when it is not.
	 *
	 * @access public
	 */
	public function _getTextResponse() {
		if ($this->Compression === true
			&& isset($this->_currentStatusResponse[1])
			&& stripos($this->_currentStatusResponse[1], 'COMPRESS=GZIP')
			!== false) {
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
	 * @return array string The array of headers.
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
			return "Error: Could not find the end-of-file pointer on the gzip stream.\n";
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
	 * @return boolean If we have failed to decode the text.
	 * @return string  The decoded result.
	 *
	 * @access protected
	 *
	 * @TODO: ? Maybe this function should be merged into the yenc class?
	 */
	protected function _decodeYenc($yencodedvar) {
		$input = array();
		preg_match('/^(=ybegin.*=yend[^$]*)$/ims', $yencodedvar, $input);
		if (isset($input[1])) {
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

			return $ret;
		}
		return false;
	}

	/**
	 * Try to see if the NNTP server implements XFeature GZip Compression,
	 * change the compression bool object if so.
	 *
	 * @note Based on this script : http://pastebin.com/A3YypDAJ
	 *
	 * @return boolean Did the server understand our request?
	 *
	 * @access protected
	 */
	protected function _enableCompression() {
		$response = $this->_sendCommand('XFEATURE COMPRESS GZIP');
		if (PEAR::isError($response) || $response != 290) {
			return false;
		}

		$this->Compression = true;
		return true;
	}
}
?>
