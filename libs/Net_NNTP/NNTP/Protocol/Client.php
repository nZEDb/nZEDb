<?php

/**
 *
 *
 * PHP versions 4 and 5
 *
 * <pre>
 * +-----------------------------------------------------------------------+
 * |                                                                       |
 * | W3C® SOFTWARE NOTICE AND LICENSE                                      |
 * | http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231   |
 * |                                                                       |
 * | This work (and included software, documentation such as READMEs,      |
 * | or other related items) is being provided by the copyright holders    |
 * | under the following license. By obtaining, using and/or copying       |
 * | this work, you (the licensee) agree that you have read, understood,   |
 * | and will comply with the following terms and conditions.              |
 * |                                                                       |
 * | Permission to copy, modify, and distribute this software and its      |
 * | documentation, with or without modification, for any purpose and      |
 * | without fee or royalty is hereby granted, provided that you include   |
 * | the following on ALL copies of the software and documentation or      |
 * | portions thereof, including modifications:                            |
 * |                                                                       |
 * | 1. The full text of this NOTICE in a location viewable to users       |
 * |    of the redistributed or derivative work.                           |
 * |                                                                       |
 * | 2. Any pre-existing intellectual property disclaimers, notices,       |
 * |    or terms and conditions. If none exist, the W3C Software Short     |
 * |    Notice should be included (hypertext is preferred, text is         |
 * |    permitted) within the body of any redistributed or derivative      |
 * |    code.                                                              |
 * |                                                                       |
 * | 3. Notice of any changes or modifications to the files, including     |
 * |    the date changes were made. (We recommend you provide URIs to      |
 * |    the location from which the code is derived.)                      |
 * |                                                                       |
 * | THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND COPYRIGHT    |
 * | HOLDERS MAKE NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR IMPLIED,    |
 * | INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR        |
 * | FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE    |
 * | OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD PARTY PATENTS,           |
 * | COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.                               |
 * |                                                                       |
 * | COPYRIGHT HOLDERS WILL NOT BE LIABLE FOR ANY DIRECT, INDIRECT,        |
 * | SPECIAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF ANY USE OF THE        |
 * | SOFTWARE OR DOCUMENTATION.                                            |
 * |                                                                       |
 * | The name and trademarks of copyright holders may NOT be used in       |
 * | advertising or publicity pertaining to the software without           |
 * | specific, written prior permission. Title to copyright in this        |
 * | software and any associated documentation will at all times           |
 * | remain with copyright holders.                                        |
 * |                                                                       |
 * +-----------------------------------------------------------------------+
 * </pre>
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @copyright  2002-2011 Heino H. Gehlsen <heino@gehlsen.dk>. All Rights Reserved.
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231
 *             W3C® SOFTWARE NOTICE AND LICENSE
 * @version    SVN: $Id: Client.php 330426 2013-05-31 14:41:46Z janpascal $
 * @link       http://pear.php.net/package/Net_NNTP
 * @see
 */

// Warn about PHP bugs
if (version_compare(PHP_VERSION, '5.2.11') === 0) {
	trigger_error('PHP bug #16657 breaks feof() on socket streams! '
	.'Connection consistency might be compromised!', E_USER_WARNING);
}

/**
 *
 */
require_once 'PEAR.php';
//require_once 'Net/NNTP/Error.php';
require_once 'Responsecode.php';

// {{{ constants

/**
 * Default host
 *
 * @access     public
 * @ignore
 */
define('NET_NNTP_PROTOCOL_CLIENT_DEFAULT_HOST', 'localhost');

/**
 * Default port
 *
 * @access     public
 * @ignore
 */
define('NET_NNTP_PROTOCOL_CLIENT_DEFAULT_PORT', '119');

// }}}
// {{{ Net_NNTP_Protocol_Client

/**
 * Low level NNTP Client
 *
 * Implements the client part of the NNTP standard acording to:
 *  - RFC 977,
 *  - RFC 2980,
 *  - RFC 850/1036, and
 *  - RFC 822/2822
 *
 * Each NNTP command is represented by a method: cmd*()
 *
 * WARNING: The Net_NNTP_Protocol_Client class is considered an internal
 *          class (and should therefore currently not be extended
 *          directly outside of the Net_NNTP package). Therefore its
 *          API is NOT required to be fully stable, for as long as such
 *          changes doesn't affect the public API of
 *          the Net_NNTP_Client class, which is considered stable.
 *
 * TODO:	cmdListActiveTimes()
 *      	cmdDistribPats()
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @version    package: 1.5.0 (stable)
 * @version    api: 0.9.0 (alpha)
 * @access     private
 * @see        Net_NNTP_Client
 */
class Net_NNTP_Protocol_Client extends PEAR
{
	// {{{ properties

	/**
	 * The socket resource being used to connect to the NNTP server.
	 *
	 * @var resource
	 * @access private
	 */
	var $_socket = null;

	/**
	 * Contains the last recieved status response code and text
	 *
	 * @var array
	 * @access private
	 */
	var $_currentStatusResponse = null;

	/**
	 *
	 *
	 * @var     object
	 * @access  private
	 */
	var $_logger = null;

	// }}}
	// {{{ constructor

	/**
	 * Constructor
	 *
	 * @access public
	 */
	function Net_NNTP_Protocol_Client() {
		//
//    	parent::PEAR('Net_NNTP_Error');
		parent::PEAR();
	}

	// }}}
	// {{{ getPackageVersion()

	/**
	 *
	 *
	 * @access public
	 */
	function getPackageVersion() {
		return '1.5.0';
	}

	// }}}
	// {{{ getApiVersion()

	/**
	 *
	 *
	 * @access public
	 */
	function getApiVersion() {
		return '0.9.0';
	}

	// }}}
	// {{{ setLogger()

	/**
	 *
	 *
	 * @param object $logger
	 *
	 * @access protected
	 */
	function setLogger($logger) {
		$this->_logger = $logger;
	}

	// }}}
	// {{{ setDebug()

	/**
	 * @deprecated
	 */
	function setDebug($debug = true) {
		trigger_error('You are using deprecated API v1.0 in '
		.'Net_NNTP_Protocol_Client: setDebug() ! Debugging in now '
		.'automatically handled when a logger is given.', E_USER_NOTICE);
	}

	// }}}
	// {{{ _sendCommand()

	/**
	 * Send command
	 *
	 * Send a command to the server. A carriage return / linefeed
	 * (CRLF) sequence will be appended to each command string before
	 * it is sent to the IMAP server.
	 *
	 * @param string $cmd The command to launch, ie: "ARTICLE 1004853"
	 *
	 * @return mixed (int) response code on success or (object)
	 *                     pear_error on failure
	 * @access private
	 */
	function _sendCommand($cmd) {
		// NNTP/RFC977 only allows command up to 512 (-2) chars.
		if (!strlen($cmd) > 510)
			return$this->throwError(
			'Failed writing to socket! (Command to long - max 510 chars)');

		/***************************************************************
		 * Credit:                                                     *
		 *    Thanks to Brendan Coles <bcoles@gmail.com>               *
		 *    (http://itsecuritysolutions.org)                         *
		 *    For pointing out possibility to inject pipelined NNTP    *
		 *    commands into pretty much any Net_NNTP command-sending   *
		 *    function with user input, by appending a new line        *
		 *    character followed by the injection.                     *
		 ***************************************************************

		 * Prevent new line (and possible future) characters in the NNTP
		 * commands Net_NNTP does not support pipelined commands.
		 * Inserting a new line charecter allows sending multiple
		 * commands and thereby making the communication between
		 * NET_NNTP and the server out of sync...
		 */
		if (preg_match_all('/\r?\n/', $cmd, $matches, PREG_PATTERN_ORDER)) {
			foreach ($matches[0] as $key => $match) {
				$this->_logger->debug("Illegal character in command: ".
				htmlentities(str_replace(array("\r","\n"),
				array("'Carriage Return'", "'New Line'"), $match)));
			}
			return $this->throwError("Illegal character(s) in NNTP command!");
		}

		// Check if connected
		if (!$this->_isConnected()) {
			return $this->throwError('Failed to write to socket! (connection lost!)');
		}

		// Send the command
		$R = @fwrite($this->_socket, $cmd . "\r\n");
		if ($R === false) {
			return $this->throwError('Failed to write to socket!');
		}

		//
		if ($this->_logger && $this->_logger->_isMasked(PEAR_LOG_DEBUG)) {
			$this->_logger->debug('C: ' . $cmd);
		}

		//
		return $this->_getStatusResponse();
	}

	// }}}
	// {{{ _getStatusResponse()

	/**
	 * Get servers status response after a command.
	 *
	 * @return mixed (int) statuscode on success or
	 *                             (object) pear_error on failure
	 * @access private
	 */
	function _getStatusResponse() {
		// Retrieve a line (terminated by "\r\n") from the server.
		// RFC says max is 510, but IETF says "be liberal in what you accept"...
		$response = @fgets($this->_socket, 4096);

		if ($response === false) {
			return $this->throwError('Failed to read from socket...!');
		}

		//
		if ($this->_logger && $this->_logger->_isMasked(PEAR_LOG_DEBUG)) {
			$this->_logger->debug('S: ' . rtrim($response, "\r\n"));
		}

		/* Trim the start of the response in case of
		 * misplased whitespace (should not be needen!!!)
		 */
		$response = ltrim($response);

		$this->_currentStatusResponse =
			array((int) substr($response, 0, 3),
				(string) rtrim(substr($response, 4)));

		//
		return $this->_currentStatusResponse[0];
	}

	// }}}
	// {{{ _getTextResponse()

	/**
	 * Retrieve textural data
	 *
	 * Get data until a line with only a '.' in it is read and return data.
	 *
	 * @return mixed (array) text response on success or
	 *                        (object) pear_error on failure
	 * @access private
	 */
	function _getTextResponse() {
		$data = array();
		$line = '';

		//
		$debug = $this->_logger && $this->_logger->_isMasked(PEAR_LOG_DEBUG);

		// Continue until connection is lost
		while (!feof($this->_socket)) {

			// Retrieve and append up to 1024 characters from the server.
			$recieved = @fgets($this->_socket, 1024);

			if ($recieved === false) {
				return $this->throwError('Failed to read line from socket.', null);
			}

			$line .= $recieved;

			// Continue if the line is not terminated by CRLF
			if (substr($line, -2) != "\r\n" || strlen($line) < 2) {
				continue;
			}

			// Validate recieved line
			if (false) {
				// Lines should/may not be longer than 998+2 chars (RFC2822 2.3)
				if (strlen($line) > 1000) {
					if ($this->_logger) {
						$this->_logger->notice('Max line length...');
					}
					return $this->throwError('Invalid line recieved!', null);
				}
			}

			// Remove CRLF from the end of the line
			$line = substr($line, 0, -2);

			// Check if the line terminates the textresponse
			if ($line == '.') {

				if ($this->_logger) {
					$this->_logger->debug('T: ' . $line);
				}

				// return all previous lines
				return $data;
			}

			// If 1st char is '.' it's doubled (NNTP/RFC977 2.4.1)
			if (substr($line, 0, 2) == '..') {
				$line = substr($line, 1);
			}

			//
			if ($debug) {
				$this->_logger->debug('T: ' . $line);
			}

			// Add the line to the array of lines
			$data[] = $line;

			// Reset/empty $line
			$line = '';
		}

		if ($this->_logger) {
			$this->_logger->warning('Broke out of reception loop! '
			.'This souldn\'t happen unless connection has been lost?');
		}

		//
		return $this->throwError('End of stream! Connection lost?', null);
	}

	// }}}
	// {{{ _sendText()

	/**
	 *
	 *
	 * @access private
	 */
	function _sendArticle($article) {
		/* data should be in the format specified by RFC850 */

		switch (true) {
			case is_string($article):
				//
				@fwrite($this->_socket, $article);
				@fwrite($this->_socket, "\r\n.\r\n");

				//
				if ($this->_logger && $this->_logger->_isMasked(PEAR_LOG_DEBUG)) {
					foreach (explode("\r\n", $article) as $line) {
					$this->_logger->debug('D: ' . $line);
					}
					$this->_logger->debug('D: .');
				}
				break;

			case is_array($article):
				//
				$header = reset($article);
				$body = next($article);

/* Experimental...
				// If header is an array, implode it.
				if (is_array($header)) {
					$header = implode("\r\n", $header) . "\r\n";
				}
*/

				// Send header (including separation line)
				@fwrite($this->_socket, $header);
				@fwrite($this->_socket, "\r\n");

				//
				if ($this->_logger && $this->_logger->_isMasked(PEAR_LOG_DEBUG)) {
					foreach (explode("\r\n", $header) as $line) {
						$this->_logger->debug('D: ' . $line);
					}
				}


/* Experimental...
				// If body is an array, implode it.
				if (is_array($body)) {
					$header = implode("\r\n", $body) . "\r\n";
				}
*/

				// Send body
				@fwrite($this->_socket, $body);
				@fwrite($this->_socket, "\r\n.\r\n");

				//
				if ($this->_logger && $this->_logger->_isMasked(PEAR_LOG_DEBUG)) {
					foreach (explode("\r\n", $body) as $line) {
						$this->_logger->debug('D: ' . $line);
					}
					$this->_logger->debug('D: .');
				}
				break;

			default:
				return $this->throwError('Ups...', null, null);
		}

		return true;
	}

	// }}}
	// {{{ _currentStatusResponse()

	/**
	 *
	 *
	 * @return string status text
	 * @access private
	 */
	function _currentStatusResponse() {
		return $this->_currentStatusResponse[1];
	}

	// }}}
	// {{{ _handleUnexpectedResponse()

	/**
	 *
	 *
	 * @param int $code Status code number
	 * @param string $text Status text
	 *
	 * @return mixed
	 * @access private
	 */
	function _handleUnexpectedResponse($code = null, $text = null) {
		if ($code === null) {
			$code = $this->_currentStatusResponse[0];
		}

		if ($text === null) {
			$text = $this->_currentStatusResponse();
		}

		switch ($code) {
			/* 502, 'access restriction or permission denied'
			 * / service permanently unavailable
			 */
			case NET_NNTP_PROTOCOL_RESPONSECODE_NOT_PERMITTED:
				return $this->throwError('Command not permitted / Access'
					.' restriction / Permission denied', $code, $text);
				break;
			default:
				return $this->throwError("Unexpected response: '$text'",
					$code, $text);
		}
	}

	// }}}

/* Session administration commands */

	// {{{ Connect()

	/**
	 * Connect to a NNTP server
	 *
	 * @param string	$host	(optional) The address of the
	 *              NNTP-server to connect to, defaults to 'localhost'.
	 * @param mixed	$encryption	(optional)
	 * @param int	$port	(optional) The port number to connect to,
	 *                       defaults to 119.
	 * @param int	$timeout	(optional)
	 *
	 * @return mixed (bool) on success (true when posting allowed,
	 *               otherwise false) or (object) pear_error on failure
	 * @access protected
	 */
	function connect($host = null, $encryption = null, $port = null, $timeout = 15) {
		//
		if ($this->_isConnected() ) {
			return $this->throwError('Already connected, disconnect first!', null);
		}

		// v1.0.x API
		if (is_int($encryption)) {
			trigger_error('You are using deprecated API v1.0 in '
			.'Net_NNTP_Protocol_Client: connect() !', E_USER_NOTICE);
			$port = $encryption;
			$encryption = false;
		}

		//
		if (is_null($host)) {
			$host = 'localhost';
		}

		/* Choose transport based on encryption, and if no port is
		 * given, use default for that encryption
		 */
		switch ($encryption) {
			case null:

			case false:
				$transport = 'tcp';
				$port = is_null($port) ? 119 : $port;
				break;

			case 'ssl':

			case 'tls':
				$transport = $encryption;
				$port = is_null($port) ? 563 : $port;
				break;

			default:
				trigger_error('$encryption parameter must be either '
				.'tcp, tls or ssl.', E_USER_ERROR);
		}

		// Open Connection
		$R = @stream_socket_client($transport . '://' . $host . ':' . $port, $errno, $errstr, $timeout);
		if ($R === false) {
			if ($this->_logger) {
				$this->_logger->notice
					("Connection to $transport://$host:$port failed.");
			}
			return $R;
		}

		$this->_socket = $R;
		stream_set_timeout($this->_socket, 120);

		//
		if ($this->_logger) {
			$this->_logger->info("Connection to $transport://$host:"
				."$port has been established.");
		}

		// Retrive the server's initial response.
		$response = $this->_getStatusResponse();
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 200, Posting allowed
			case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED:
				// TODO: Set some variable before return
				return true;
				break;

			 // 201, Posting NOT allowed
			case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_PROHIBITED:
				//
				if ($this->_logger) {
					$this->_logger->info('Posting not allowed!');
				}

				// TODO: Set some variable before return
				return false;
				break;

			case 400:
				return $this->throwError('Server refused connection',
						$response, $this->_currentStatusResponse());
				break;

			/* 502, 'access restriction or permission denied'
			 * / service permanently unavailable
			 */
			case NET_NNTP_PROTOCOL_RESPONSECODE_NOT_PERMITTED:
				return $this->throwError('Server refused connection',
						$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ disconnect()

	/**
	 * alias for cmdQuit()
	 *
	 * @access protected
	 */
	function disconnect() {
		return $this->cmdQuit();
	}

	// }}}
	// {{{ cmdCapabilities()

	/**
	 * Returns servers capabilities
	 *
	 * @return mixed (array) list of capabilities on success or
	 *                       (object) pear_error on failure
	 * @access protected
	 */
	function cmdCapabilities() {
		// tell the newsserver we want an article
		$response = $this->_sendCommand('CAPABILITIES');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 101, Draft: 'Capability list follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_CAPABILITIES_FOLLOW:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}
				return $data;
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdModeReader()

	/**
	 *
	 *
	 * @return mixed (bool) true when posting allowed, false when
	 *             postind disallowed or (object) pear_error on failure
	 * @access protected
	 */
	function cmdModeReader() {
		// tell the newsserver we want an article
		$response = $this->_sendCommand('MODE READER');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 200, RFC2980: 'Hello, you can post'
			case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED:
				// TODO: Set some variable before return
				return true;
				break;

			// 201, RFC2980: 'Hello, you can't post'
			case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_PROHIBITED:
				if ($this->_logger) {
					$this->_logger->info('Posting not allowed!');
				}
				// TODO: Set some variable before return
				return false;
				break;

			/* 502, 'access restriction or permission denied'
			 * / service permanently unavailable
			 */
			case NET_NNTP_PROTOCOL_RESPONSECODE_NOT_PERMITTED:
				return $this->throwError('Connection being closed, '
					.'since service so permanently unavailable',
					$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdQuit()

	/**
	 * Disconnect from the NNTP server
	 *
	 * @return mixed (bool) true on success or
	 *                                 (object) pear_error on failure
	 * @access protected
	 */
	function cmdQuit() {
		// Tell the server to close the connection
		$response = $this->_sendCommand('QUIT');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			case 205: // RFC977: 'closing connection - goodbye!'
				// If socket is still open, close it.
				if ($this->_isConnected()) {
					fclose($this->_socket);
				}

				if ($this->_logger) {
					$this->_logger->info('Connection closed.');
				}
				return true;
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}

/* */

	// {{{ cmdStartTLS()

	/**
	 *
	 *
	 * @return mixed (bool) on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdStartTLS() {
		$response = $this->_sendCommand('STARTTLS');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			case 382: // RFC4642: 'continue with TLS negotiation'
				$encrypted = stream_socket_enable_crypto($this->_socket,
				true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
				switch (true) {
					case $encrypted === true:
						if ($this->_logger) {
							$this->_logger->info('TLS encryption started.');
						}
						return true;
						break;

					case $encrypted === true:
						if ($this->_logger) {
							$this->_logger->info('TLS encryption failed.');
						}
						return $this->throwError('Could not initiate '
							.'TLS negotiation', $response,
							$this->_currentStatusResponse());
						break;

					case is_int($encrypted):
						return $this->throwError('', $response,
							$this->_currentStatusResponse());
						break;

					default:
						return $this->throwError('Internal error - '
							.'unknown response from'
							.'stream_socket_enable_crypto()',
							$response, $this->_currentStatusResponse());
				}
				break;

			case 580: // RFC4642: 'can not initiate TLS negotiation'
				return $this->throwError('', $response,
					$this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}

/* Article posting and retrieval */

	/* Group and article selection */

	// {{{ cmdGroup()

	/**
	 * Selects a news group (issue a GROUP command to the server)
	 *
	 * @param string $newsgroup The newsgroup name
	 *
	 * @return mixed (array) groupinfo on success or
	 *                                   (object) pear_error on failure
	 * @access protected
	 */
	function cmdGroup($newsgroup) {
		$response = $this->_sendCommand('GROUP '.$newsgroup);
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 211, RFC977: 'n f l s group selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_GROUP_SELECTED:
				$response_arr = explode(' ',
					trim($this->_currentStatusResponse()));

				if ($this->_logger) {
					$this->_logger->info('Group selected: '
						.$response_arr[3]);
				}

				return array('group' => $response_arr[3],
							 'first' => $response_arr[1],
							 'last'  => $response_arr[2],
							 'count' => $response_arr[0]);
				break;

			// 411, RFC977: 'no such news group'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_GROUP:
				return $this->throwError('No such news group on server',
					$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdListgroup()

	/**
	 *
	 *
	 * @param optional string $newsgroup
	 * @param optional mixed $range
	 *
	 * @return optional mixed (array) on success or
	 *                                   (object) pear_error on failure
	 * @access protected
	 */
	function cmdListgroup($newsgroup = null, $range = null) {
		if (is_null($newsgroup)) {
			$command = 'LISTGROUP';
		} else {
			if (is_null($range)) {
				$command = 'LISTGROUP ' . $newsgroup;
			} else {
				$command = 'LISTGROUP ' . $newsgroup . ' ' . $range;
			}
		}

		$response = $this->_sendCommand($command);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 211, RFC2980: 'list of article numbers follow'
			case NET_NNTP_PROTOCOL_RESPONSECODE_GROUP_SELECTED:

				$articles = $this->_getTextResponse();
				if ($this->isError($articles)) {
					return $articles;
				}

				$response_arr = explode(' ',
					trim($this->_currentStatusResponse()), 4);

				/* If server does not return group summary in
				 * status response, return null'ed array
				 */
				if (!is_numeric($response_arr[0]) ||
					!is_numeric($response_arr[1]) ||
					!is_numeric($response_arr[2]) ||
					empty($response_arr[3])) {
					return array('group'    => null,
								'first'     => null,
								'last'      => null,
								'count'     => null,
								'articles'  => $articles);
				}

				return array('group'    => $response_arr[3],
							 'first'    => $response_arr[1],
							 'last'     => $response_arr[2],
							 'count'    => $response_arr[0],
							 'articles' => $articles);
				break;

			// 412, RFC2980: 'Not currently in newsgroup'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('Not currently in newsgroup',
					$response, $this->_currentStatusResponse());
				break;

			// RFC2980: 'no permission'
			case 502:
				return $this->throwError('No permission', $response,
					$this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdLast()

	/**
	 *
	 *
	 * @return mixed (array) or (string) or (int) or
	 *                                   (object) pear_error on failure
	 * @access protected
	 */
	function cmdLast() {
		//
		$response = $this->_sendCommand('LAST');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			/* 223, RFC977: 'n a article retrieved - request text
			 * separately (n = article number, a = unique article id)'
			 */
			case NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED:
				$response_arr = explode(' ',
					trim($this->_currentStatusResponse()));

				if ($this->_logger) {
					$this->_logger->info('Selected previous article: ' .
						$response_arr[0] .' - '. $response_arr[1]);
				}

				return array($response_arr[0], (string) $response_arr[1]);
				break;

			// 412, RFC977: 'no newsgroup selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('No newsgroup has been selected',
					$response, $this->_currentStatusResponse());
				break;

			// 420, RFC977: 'no current article has been selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED:
				return $this->throwError('No current article has been selected',
					$response, $this->_currentStatusResponse());
				break;

			// 422, RFC977: 'no previous article in this group'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_PREVIOUS_ARTICLE:
				return $this->throwError('No previous article in this group',
					$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdNext()

	/**
	 *
	 *
	 * @return mixed (array) or (string) or (int) or
	 *                                   (object) pear_error on failure
	 * @access protected
	 */
	function cmdNext() {
		//
		$response = $this->_sendCommand('NEXT');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			/* 223, RFC977: 'n a article retrieved - request text
			 * separately (n = article number, a = unique article id)'
			 */
			case NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED:
				$response_arr = explode(' ',
					trim($this->_currentStatusResponse()));

				if ($this->_logger) {
					$this->_logger->info('Selected previous article: ' .
						$response_arr[0] .' - '. $response_arr[1]);
				}

				return array($response_arr[0], (string) $response_arr[1]);
				break;

			// 412, RFC977: 'no newsgroup selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('No newsgroup has been selected',
					$response, $this->_currentStatusResponse());
				break;

			// 420, RFC977: 'no current article has been selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED:
				return $this->throwError('No current article has been selected',
					$response, $this->_currentStatusResponse());
				break;

			// 421, RFC977: 'no next article in this group'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_NEXT_ARTICLE:
				return $this->throwError('No next article in this group',
					$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}

	/* Retrieval of articles and article sections */

	// {{{ cmdArticle()

	/**
	 * Get an article from the currently open connection.
	 *
	 * @param mixed $article Either a message-id or a message-number
	 * of the article to fetch. If null or '', then use current article.
	 *
	 * @return mixed (array) article on success or
	 *                                   (object) pear_error on failure
	 * @access protected
	 */
	function cmdArticle($article = null) {
		if (is_null($article)) {
			$command = 'ARTICLE';
		} else {
			$command = 'ARTICLE ' . $article;
		}

		// tell the newsserver we want an article
		$response = $this->_sendCommand($command);
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			/* 220, RFC977: 'n <a> article retrieved - head and
			 * body follow (n = article number, <a> = message-id)'
			 */
			case NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_FOLLOWS:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				if ($this->_logger) {
					$this->_logger->info(($article == null ?
						'Fetched current article' : 'Fetched article: '.
						$article));
				}
				return $data;
				break;

			// 412, RFC977: 'no newsgroup has been selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('No newsgroup has been selected',
					$response, $this->_currentStatusResponse());
				break;

			// 420, RFC977: 'no current article has been selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED:
				return $this->throwError('No current article has been selected',
					$response, $this->_currentStatusResponse());
				break;

			// 423, RFC977: 'no such article number in this group'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER:
				return $this->throwError('No such article number in this group',
					$response, $this->_currentStatusResponse());
				break;

			// 430, RFC977: 'no such article found'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID:
				return $this->throwError('No such article found',
				$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdHead()

	/**
	 * Get the headers of an article from the currently open connection.
	 *
	 * @param mixed $article Either a message-id or a message-number
	 *    of the article to fetch the headers from. If null or '',
	 *    then use current article.
	 *
	 * @return mixed (array) headers on success or
	 *                                   (object) pear_error on failure
	 * @access protected
	 */
	function cmdHead($article = null) {
		if (is_null($article)) {
			$command = 'HEAD';
		} else {
			$command = 'HEAD ' . $article;
		}

		// tell the newsserver we want the header of an article
		$response = $this->_sendCommand($command);
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 221, RFC977: 'n <a> article retrieved - head follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_HEAD_FOLLOWS:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				if ($this->_logger) {
					$this->_logger->info(($article == null ?
						'Fetched current article header' : 'Fetched '
						.'article header for article: '.$article));
				}

				return $data;
				break;

			// 412, RFC977: 'no newsgroup has been selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('No newsgroup has been selected',
					$response, $this->_currentStatusResponse());
				break;

			// 420, RFC977: 'no current article has been selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED:
				return $this->throwError('No current article has been selected',
					$response, $this->_currentStatusResponse());
				break;

			// 423, RFC977: 'no such article number in this group'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER:
				return $this->throwError('No such article number in this group',
					$response, $this->_currentStatusResponse());
				break;

			// 430, RFC977: 'no such article found'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID:
				return $this->throwError('No such article found',
					$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdBody()

	/**
	 * Get the body of an article from the currently open connection.
	 *
	 * @param mixed $article Either a message-id or a message-number of
	 *    the article to fetch the body from. If null or '',
	 *    then use current article.
	 *
	 * @return mixed (array) body on success or
	 *                                   (object) pear_error on failure
	 * @access protected
	 */
	function cmdBody($article = null) {
		if (is_null($article)) {
			$command = 'BODY';
		} else {
			$command = 'BODY ' . $article;
		}

		// tell the newsserver we want the body of an article
		$response = $this->_sendCommand($command);
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 222, RFC977: 'n <a> article retrieved - body follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_BODY_FOLLOWS:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				if ($this->_logger) {
					$this->_logger->info(($article == null ?
						'Fetched current article body' : 'Fetched '
						.'article body for article: '.$article));
				}

				return $data;
				break;

			// 412, RFC977: 'no newsgroup has been selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('No newsgroup has been selected',
					$response, $this->_currentStatusResponse());
				break;

			// 420, RFC977: 'no current article has been selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED:
				return $this->throwError('No current article has been selected',
					$response, $this->_currentStatusResponse());
				break;

			// 423, RFC977: 'no such article number in this group'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER:
				return $this->throwError('No such article number in this group',
					$response, $this->_currentStatusResponse());
				break;

			// 430, RFC977: 'no such article found'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID:
				return $this->throwError('No such article found',
					$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdStat

	/**
	 *
	 *
	 * @param mixed $article
	 *
	 * @return mixed (array) or (string) or (int) or
	 *                                   (object) pear_error on failure
	 * @access protected
	 */
	function cmdStat($article = null) {
		if (is_null($article)) {
			$command = 'STAT';
		} else {
			$command = 'STAT ' . $article;
		}

		// tell the newsserver we want an article
		$response = $this->_sendCommand($command);
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			/* 223, RFC977: 'n <a> article retrieved - request text
			 * separately' (actually not documented, but copied
			 * from the ARTICLE command)
			 */
			case NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED:
				$response_arr = explode(' ',
					trim($this->_currentStatusResponse()));

				if ($this->_logger) {
					$this->_logger->info('Selected article: ' .
						$response_arr[0].' - '.$response_arr[1]);
				}

				return array($response_arr[0], (string) $response_arr[1]);
				break;

			/* 412, RFC977: 'no newsgroup has been selected'
			 * (actually not documented, but copied from the
			 * ARTICLE command)
			 */
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('No newsgroup has been selected',
					$response, $this->_currentStatusResponse());
				break;

			/* 423, RFC977: 'no such article number in this group'
			 * (actually not documented, but copied from the
			 * ARTICLE command)
			 */
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER:
				return $this->throwError('No such article number in this group',
					$response, $this->_currentStatusResponse());
				break;

			/* 430, RFC977: 'no such article found' (actually not
			 * documented, but copied from the ARTICLE command)
			 */
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID:
				return $this->throwError('No such article found',
					$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}

	/* Article posting */

	// {{{ cmdPost()

	/**
	 * Post an article to a newsgroup.
	 *
	 * @return mixed (bool) true on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdPost() {
		// tell the newsserver we want to post an article
		$response = $this->_sendCommand('POST');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 340, RFC977: 'send article to be posted. End with <CR-LF>.<CR-LF>'
			case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SEND:
				return true;
				break;

			// 440, RFC977: 'posting not allowed'
			case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_PROHIBITED:
				return $this->throwError('Posting not allowed',
					$response, $this->_currentStatusResponse());
				break;
			default:
				return $this->_handleUnexpectedResponse($response);
		}

	}

	// }}}
	// {{{ cmdPost2()

	/**
	 * Post an article to a newsgroup.
	 *
	 * @param mixed $article (string/array)
	 *
	 * @return mixed (bool) true on success or
	 *                                  (object) pear_error on failure
	 * @access protected
	 */
	function cmdPost2($article) {
		/* should be presented in the format specified by RFC850 */

		//
		$this->_sendArticle($article);

		// Retrive server's response.
		$response = $this->_getStatusResponse();
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 240, RFC977: 'article posted ok'
			case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SUCCESS:
				return true;
				break;

			// 441, RFC977: 'posting failed'
			case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_FAILURE:
				return $this->throwError('Posting failed', $response,
					$this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdIhave()

	/**
	 *
	 *
	 * @param string $id
	 *
	 * @return mixed (bool) true on success or
	 *                                  (object) pear_error on failure
	 * @access protected
	 */
	function cmdIhave($id) {
		// tell the newsserver we want to post an article
		$response = $this->_sendCommand('IHAVE ' . $id);
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SEND: // 335
				return true;
				break;

			case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_UNWANTED: // 435
				return $this->throwError('Article not wanted',
						$response, $this->_currentStatusResponse());
				break;

			case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_FAILURE: // 436
				return $this->throwError
					('Transfer not possible; try again later',
						$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdIhave2()

	/**
	 *
	 *
	 * @param mixed $article (string/array)
	 *
	 * @return mixed (bool) true on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdIhave2($article) {
		/* should be presented in the format specified by RFC850 */

		//
		$this->_sendArticle($article);

		// Retrive server's response.
		$response = $this->_getStatusResponse();
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SUCCESS: // 235
				return true;
				break;

			case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_FAILURE: // 436
				return $this->throwError
					('Transfer not possible; try again later',
						$response, $this->_currentStatusResponse());
				break;

			case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_REJECTED: // 437
				return $this->throwError
					('Transfer rejected; do not retry',
						$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}

/* Information commands */

	// {{{ cmdDate()

	/**
	 * Get the date from the newsserver format of returned date
	 *
	 * @return mixed (string) 'YYYYMMDDhhmmss' / (int) timestamp on
	 *                       success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdDate() {
		$response = $this->_sendCommand('DATE');
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 111, RFC2980: 'YYYYMMDDhhmmss'
			case NET_NNTP_PROTOCOL_RESPONSECODE_SERVER_DATE:
				return $this->_currentStatusResponse();
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}
	// }}}
	// {{{ cmdHelp()

	/**
	 * Returns the server's help text
	 *
	 * @return mixed (array) help text on success or
	 *                                (object) pear_error on failure
	 * @access protected
	 */
	function cmdHelp() {
		// tell the newsserver we want an article
		$response = $this->_sendCommand('HELP');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			case NET_NNTP_PROTOCOL_RESPONSECODE_HELP_FOLLOWS: // 100
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}
				return $data;
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdNewgroups()

	/**
	 * Fetches a list of all newsgroups created since a specified date.
	 *
	 * @param int $time Last time you checked for groups (timestamp).
	 * @param optional string $distributions (deprecaded in rfc draft)
	 *
	 * @return mixed (array) nested array with informations about
	 *  existing newsgroups on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdNewgroups($time, $distributions = null) {
		$date = gmdate('ymd His', $time);

		if (is_null($distributions)) {
			$command = 'NEWGROUPS ' . $date . ' GMT';
		} else {
			$command = 'NEWGROUPS ' . $date . ' GMT <' .
				$distributions . '>';
		}

		$response = $this->_sendCommand($command);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 231, REF977: 'list of new newsgroups follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NEW_GROUPS_FOLLOW:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				$groups = array();
				foreach($data as $line) {
					$arr = explode(' ', trim($line));

					$group = array('group'    => $arr[0],
									'last'    => $arr[1],
									'first'   => $arr[2],
									'posting' => $arr[3]);

					$groups[$group['group']] = $group;
				}
				return $groups;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdNewnews()

	/**
	 *
	 *
	 * @param timestamp $time
	 * @param mixed $newsgroups (string or array of strings)
	 * @param mixed $distribution (string or array of strings)
	 *
	 * @return mixed
	 * @access protected
	 */
	function cmdNewnews($time, $newsgroups, $distribution = null) {
		$date = gmdate('ymd His', $time);

		if (is_array($newsgroups)) {
			$newsgroups = implode(',', $newsgroups);
		}

		if (is_null($distribution)) {
			$command = 'NEWNEWS ' . $newsgroups . ' ' . $date . ' GMT';
		} else {
			if (is_array($distribution)) {
			$distribution = implode(',', $distribution);
			}

			$command = 'NEWNEWS ' . $newsgroups . ' ' . $date . ' GMT <'
				. $distribution . '>';
		}

		// TODO: the lenght of the request string may not exceed 510 chars

		$response = $this->_sendCommand($command);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 230, RFC977: 'list of new articles by message-id follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NEW_ARTICLES_FOLLOW:
				$messages = array();
				foreach($this->_getTextResponse() as $line) {
					$messages[] = $line;
				}
				return $messages;
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}

	/* The LIST commands */

	// {{{ cmdList()

	/**
	 * Fetches a list of all avaible newsgroups
	 *
	 * @return mixed (array) nested array with informations about
	 *  existing newsgroups on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdList() {
		$response = $this->_sendCommand('LIST');
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 215, RFC977: 'list of newsgroups follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				$groups = array();
				foreach($data as $line) {
					$arr = explode(' ', trim($line));

					$group = array('group'    => $arr[0],
									'last'    => $arr[1],
									'first'   => $arr[2],
									'posting' => $arr[3]);

					$groups[$group['group']] = $group;
				}
				return $groups;
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdListActive()

	/**
	 * Fetches a list of all avaible newsgroups
	 *
	 * @param string $wildmat
	 *
	 * @return mixed (array) nested array with informations about
	 *  existing newsgroups on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdListActive($wildmat = null) {
		if (is_null($wildmat)) {
			$command = 'LIST ACTIVE';
		} else {
			$command = 'LIST ACTIVE ' . $wildmat;
		}

		$response = $this->_sendCommand($command);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 215, RFC977: 'list of newsgroups follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				$groups = array();
				foreach($data as $line) {
					$arr = explode(' ', trim($line));

					$group = array('group'    => $arr[0],
									'last'    => $arr[1],
									'first'   => $arr[2],
									'posting' => $arr[3]);

					$groups[$group['group']] = $group;
				}

				if ($this->_logger) {
					$this->_logger->info('Fetched list of available groups');
				}

				return $groups;
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdListNewsgroups()

	/**
	 * Fetches a list of (all) avaible newsgroup descriptions.
	 *
	 * @param string $wildmat Wildmat of the groups, that is to be
	 *                         listed, defaults to null;
	 *
	 * @return mixed (array) nested array with description of existing
	 *          newsgroups on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdListNewsgroups($wildmat = null) {
		if (is_null($wildmat)) {
			$command = 'LIST NEWSGROUPS';
		} else {
			$command = 'LIST NEWSGROUPS ' . $wildmat;
		}

		$response = $this->_sendCommand($command);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 215, RFC2980: 'information follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				$groups = array();

				foreach($data as $line) {
					if (preg_match("/^(\S+)\s+(.*)$/", ltrim($line),
						$matches)) {
						$groups[$matches[1]] = (string) $matches[2];
					} else {
						if ($this->_logger) {
							$this->_logger->warning
								("Recieved non-standard line: '$line'");
						}
					}
				}

				if ($this->_logger) {
					$this->_logger->info('Fetched group descriptions');
				}

				return $groups;
			break;

			// RFC2980: 'program error, function not performed'
			case 503:
				return $this->throwError
					('Internal server error, function not performed',
						$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}

/* Article field access commands */

	// {{{ cmdOver()

	/**
	 * Fetch message header from message number $first until $last
	 *
	 * The format of the returned array is:
	 * $messages[][header_name]
	 *
	 * @param optional string $range articles to fetch
	 *
	 * @return mixed (array) nested array of message and there headers
	 *                    on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdOver($range = null) {
		if (is_null($range)) {
		$command = 'OVER';
		} else {
			$command = 'OVER ' . $range;
		}

		$response = $this->_sendCommand($command);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 224, RFC2980: 'Overview information follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				foreach ($data as $key => $value) {
					$data[$key] = explode("\t", trim($value));
				}

				if ($this->_logger) {
					$this->_logger->info('Fetched overview ' .
						($range == null ?
							'for current article' : 'for range: '.$range));
				}

				return $data;
				break;

			// 412, RFC2980: 'No news group current selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('No news group current selected',
					$response, $this->_currentStatusResponse());
				break;

			// 420, RFC2980: 'No article(s) selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED:
				return $this->throwError('No article(s) selected',
					$response, $this->_currentStatusResponse());
				break;

			// 423:, Draft27: 'No articles in that range'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER:
				return $this->throwError('No articles in that range',
					$response, $this->_currentStatusResponse());
				break;

			// RFC2980: 'no permission'
			case 502:
				return $this->throwError('No permission',
					$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdXOver()

	/**
	 * Fetch message header from message number $first until $last
	 *
	 * The format of the returned array is:
	 * $messages[message_id][header_name]
	 *
	 * @param optional string $range articles to fetch
	 *
	 * @return mixed (array) nested array of message and there headers
	 *                    on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdXOver($range = null) {
		// deprecated API (the code _is_ still in alpha state)
		if (func_num_args() > 1 ) {
			die('The second parameter in cmdXOver() has been '
				.'deprecated! Use x-y instead...');
		}

		if (is_null($range)) {
		$command = 'XOVER';
		} else {
			$command = 'XOVER ' . $range;
		}

		$response = $this->_sendCommand($command);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 224, RFC2980: 'Overview information follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				foreach ($data as $key => $value) {
					$data[$key] = explode("\t", trim($value));
				}

				if ($this->_logger) {
					$this->_logger->info('Fetched overview ' .
						($range == null ?
							'for current article' : 'for range: '.$range));
				}

				return $data;
				break;

			// 412, RFC2980: 'No news group current selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('No news group current selected',
					$response, $this->_currentStatusResponse());
				break;

			// 420, RFC2980: 'No article(s) selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED:
				return $this->throwError('No article(s) selected',
					$response, $this->_currentStatusResponse());
				break;

			// RFC2980: 'no permission'
			case 502:
				return $this->throwError('No permission',
					$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdListOverviewFmt()

	/**
	 * Returns a list of avaible headers which are send from newsserver
	 *                               to client for every news message
	 *
	 * @return mixed (array) of header names on success or
	 *                                   (object) pear_error on failure
	 * @access protected
	 */
	function cmdListOverviewFmt() {
		$response = $this->_sendCommand('LIST OVERVIEW.FMT');
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 215, RFC2980: 'information follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				$format = array();

				foreach ($data as $line) {

					// Check if postfixed by ':full' (case-insensitive)
					if (0 == strcasecmp(substr($line, -5, 5), ':full')) {
						// ':full' is _not_ included in tag, but value set to true
						$format[substr($line, 0, -5)] = true;
					} else {
						// ':' is _not_ included in tag; value set to false
						$format[substr($line, 0, -1)] = false;
						}
				}

				if ($this->_logger) {
					$this->_logger->info('Fetched overview format');
				}
				return $format;
				break;

			// RFC2980: 'program error, function not performed'
			case 503:
				return $this->throwError
					('Internal server error, function not performed',
						$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdXHdr()

	/**
	 *
	 *
	 * The format of the returned array is:
	 * $messages[message_id]
	 *
	 * @param optional string $field
	 * @param optional string $range articles to fetch
	 *
	 * @return mixed (array) nested array of message and there header
	 *                 on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdXHdr($field, $range = null) {
		if (is_null($range)) {
		$command = 'XHDR ' . $field;
		} else {
			$command = 'XHDR ' . $field . ' ' . $range;
		}

		$response = $this->_sendCommand($command);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 221, RFC2980: 'Header follows'
			case 221:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				$return = array();
				foreach($data as $line) {
					$line = explode(' ', trim($line), 2);
					$return[$line[0]] = $line[1];
				}

				return $return;
				break;

			// 412, RFC2980: 'No news group current selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('No news group current selected',
					$response, $this->_currentStatusResponse());
				break;

			// 420, RFC2980: 'No current article selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED:
				return $this->throwError('No current article selected',
					$response, $this->_currentStatusResponse());
				break;

			// 430, RFC2980: 'No such article'
			case 430:
				return $this->throwError('No such article',
					$response, $this->_currentStatusResponse());
				break;

			// RFC2980: 'no permission'
			case 502:
				return $this->throwError('No permission',
					$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdXGTitle()

	/**
	 * Fetches a list of (all) avaible newsgroup descriptions.
	 * Depresated as of RFC2980.
	 *
	 * @param string $wildmat Wildmat of the groups, that is
	 *                   to be listed, defaults to '*';
	 *
	 * @return mixed (array) nested array with description of
	 *  existing newsgroups on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdXGTitle($wildmat = '*') {
		$response = $this->_sendCommand('XGTITLE '.$wildmat);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// RFC2980: 'list of groups and descriptions follows'
			case 282:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				$groups = array();

				foreach($data as $line) {
					preg_match("/^(.*?)\s(.*?$)/", trim($line), $matches);
					$groups[$matches[1]] = (string) $matches[2];
				}

				return $groups;
				break;

			case 481: // RFC2980: 'Groups and descriptions unavailable'
				return $this->throwError
					('Groups and descriptions unavailable',
						$response, $this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdXROver()

	/**
	 * Fetch message references from message number $first to $last
	 *
	 * @param optional string $range articles to fetch
	 *
	 * @return mixed (array) assoc. array of message references on
	 *                        success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdXROver($range = null) {
		// Warn about deprecated API (the code _is_ still in alpha state)
		if (func_num_args() > 1 ) {
			die('The second parameter in cmdXROver() has been '
				.'deprecated! Use x-y instead...');
		}

		if (is_null($range)) {
			$command = 'XROVER';
		} else {
			$command = 'XROVER ' . $range;
		}

		$response = $this->_sendCommand($command);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 224, RFC2980: 'Overview information follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				$return = array();
				foreach($data as $line) {
					$line = explode(' ', trim($line), 2);
					$return[$line[0]] = $line[1];
				}
				return $return;
				break;

			// 412, RFC2980: 'No news group current selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('No news group current selected',
					$response, $this->_currentStatusResponse());
				break;

			// 420, RFC2980: 'No article(s) selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED:
				return $this->throwError('No article(s) selected',
					$response, $this->_currentStatusResponse());
				break;

			// RFC2980: 'no permission'
			case 502:
				return $this->throwError('No permission', $response,
					$this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdXPat()

	/**
	 *
	 *
	 * @param string $field
	 * @param string $range
	 * @param mixed $wildmat
	 *
	 * @return mixed (array) nested array of message and there
	 *        headers on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdXPat($field, $range, $wildmat) {
		if (is_array($wildmat)) {
		$wildmat = implode(' ', $wildmat);
		}

		$response = $this->_sendCommand('XPAT ' . $field . ' ' .
			$range . ' ' . $wildmat);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			case 221: // 221, RFC2980: 'Header follows'
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				$return = array();
				foreach($data as $line) {
					$line = explode(' ', trim($line), 2);
					$return[$line[0]] = $line[1];
				}

				return $return;
				break;

			case 430: // 430, RFC2980: 'No such article'
				return $this->throwError('No current article selected',
					$response, $this->_currentStatusResponse());
				break;

			case 502: // RFC2980: 'no permission'
				return $this->throwError('No permission', $response,
					$this->_currentStatusResponse());
				break;

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdAuthinfo()

	/**
	 * Authenticate using 'original' method
	 *
	 * @param string $user The username to authenticate as.
	 * @param string $pass The password to authenticate with.
	 *
	 * @return mixed (bool) true on success or
	 *                                  (object) pear_error on failure
	 * @access protected
	 */
	function cmdAuthinfo($user, $pass) {
		// Send the username
		$response = $this->_sendCommand('AUTHINFO user '.$user);
		if ($this->isError($response)) {
			return $response;
		}

		// Send the password, if the server asks
		if (($response == 381) && ($pass !== null)) {
			// Send the password
			$response = $this->_sendCommand('AUTHINFO pass '.$pass);
			if ($this->isError($response)) {
				return $response;
			}
		}

		switch ($response) {
			case 281: // RFC2980: 'Authentication accepted'
				if ($this->_logger) {
					$this->_logger->info("Authenticated (as user '$user')");
				}

				// TODO: Set some variable before return

				return true;
				break;

			case 381: // RFC2980: 'More authentication information required'
				return $this->throwError('Authentication uncompleted',
					$response, $this->_currentStatusResponse());
				break;

			case 482: // RFC2980: 'Authentication rejected'
				return $this->throwError('Authentication rejected',
					$response, $this->_currentStatusResponse());
				break;

			case 502: // RFC2980: 'No permission'
				return $this->throwError('Authentication rejected',
					$response, $this->_currentStatusResponse());
				break;

			/*case 500:
			case 501:
				return $this->throwError('Authentication failed', $response, $this->_currentStatusResponse());
				break;*/

			default:
				return $this->_handleUnexpectedResponse($response);
		}
	}

	// }}}
	// {{{ cmdAuthinfoSimple()

	/**
	 * Authenticate using 'simple' method
	 *
	 * @param string $user The username to authenticate as.
	 * @param string $pass The password to authenticate with.
	 *
	 * @return mixed (bool) true on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdAuthinfoSimple($user, $pass) {
		return $this->throwError
			("The auth mode: 'simple' is has not been implemented yet",
				null);
	}

	// }}}
	// {{{ cmdAuthinfoGeneric()

	/**
	 * Authenticate using 'generic' method
	 *
	 * @param string $user The username to authenticate as.
	 * @param string $pass The password to authenticate with.
	 *
	 * @return mixed (bool) true on success or (object) pear_error on failure
	 * @access protected
	 */
	function cmdAuthinfoGeneric($user, $pass) {
		return $this->throwError
			("The auth mode: 'generic' is has not been implemented yet",
				null);
	}

	// }}}
	// {{{ _isConnected()

	/**
	 * Test whether we are connected or not.
	 *
	 * @return bool true or false
	 * @access protected
	 */
	function _isConnected() {
		return (is_resource($this->_socket) && (!feof($this->_socket)));
	}

	// }}}

}

// }}}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
