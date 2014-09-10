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
	trigger_error('PHP bug #16657 breaks feof() on socket streams! Connection consistency might be compromised!', E_USER_WARNING);
}

require_once 'PEAR.php';
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
 * Implements the client part of the NNTP standard according to:
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
 *          changes do not affect the public API of
 *          the Net_NNTP_Client class, which is considered stable.
 *
 * TODO: cmdListActiveTimes()
 *       cmdDistribPats()
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
	 * @access protected
	 */
	protected $_socket = null;

	/**
	 * Contains the last received status response code and text.
	 *
	 * @var array
	 * @access protected
	 */
	protected $_currentStatusResponse = null;

	/**
	 * Optional logger class to use for debugging.
	 *
	 * @var     object
	 * @access  private
	 */
	private $_logger = null;

	/**
	 * Seconds to wait for the blocking socket to timeout.
	 *
	 * @var int
	 */
	protected $_socketTimeout = 120;

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function Net_NNTP_Protocol_Client()
	{
		// Init PEAR.
		parent::PEAR();
	}

	/**
	 * Get current package version.
	 *
	 * @access public
	 */
	public function getPackageVersion()
	{
		return '1.5.0';
	}

	/**
	 * Get current API version.
	 *
	 * @access public
	 */
	public function getApiVersion()
	{
		return '0.9.0';
	}

	/**
	 * Set the debug logger instance object.
	 *
	 * @param object $logger
	 *
	 * @access protected
	 */
	protected function setLogger($logger)
	{
		$this->_logger = $logger;
	}

	/**
	 * @deprecated
	 */
	public function setDebug($debug = true)
	{
		trigger_error(
			'You are using deprecated API v1.0 in Net_NNTP_Protocol_Client: setDebug() ! Debugging in now automatically handled when a logger is given.',
			E_USER_NOTICE
		);
	}

	/**
	 * Send a command to usenet.
	 *
	 * Send a command to the server. A carriage return / linefeed
	 * (CRLF) sequence will be appended to each command string before it is sent to the IMAP server.
	 *
	 * @param string $cmd The command to launch, ie: "ARTICLE 1004853"
	 *
	 * @return mixed (int)    response code on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function _sendCommand($cmd)
	{
		// NNTP/RFC977 only allows command up to 512 (-2) chars.
		if (strlen($cmd) > 510) {
			return$this->throwError('Failed writing to socket! (Command to long - max 510 chars)');
		}

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
		 * Inserting a new line character allows sending multiple
		 * commands and thereby making the communication between
		 * NET_NNTP and the server out of sync...
		 */
		if (preg_match_all('/\r?\n/', $cmd, $matches, PREG_PATTERN_ORDER)) {
			if ($this->_logger) {
				foreach ($matches[0] as $match) {
					$this->_logger->debug(
						"Illegal character in command: " . htmlentities(str_replace(array("\r","\n"),
						array("'Carriage Return'", "'New Line'"), $match))
					);
				}
			}
			return $this->throwError("Illegal character(s) in NNTP command!");
		}

		// Check if connected.
		if (!$this->_isConnected()) {
			return $this->throwError('Failed to write to socket! (connection lost!)');
		}

		// Send the command.
		if (@fwrite($this->_socket, $cmd . "\r\n") === false) {
			return $this->throwError('Failed to write to socket!');
		}

		// Log sent message.
		if ($this->_logger && $this->_logger->_isMasked(PEAR_LOG_DEBUG)) {
			$this->_logger->debug('C: ' . $cmd);
		}

		return $this->_getStatusResponse();
	}

	/**
	 * Get servers status response after a command.
	 *
	 * @return mixed (int)    response code on success
	 *               (object) pear_error on failure
	 * @access private
	 */
	private function _getStatusResponse()
	{
		// Retrieve a line (terminated by "\r\n") from the server.  RFC says max is 510, but IETF says "be liberal in what you accept"...
		$response = @fgets($this->_socket, 4096);

		if ($response === false) {
			return $this->throwError('Failed to read from socket...!');
		}

		// Log incoming message.
		if ($this->_logger && $this->_logger->_isMasked(PEAR_LOG_DEBUG)) {
			$this->_logger->debug('S: ' . rtrim($response, "\r\n"));
		}

		// Trim the start of the response in case of misplaced whitespace (should not be needed).
		$response = ltrim($response);

		// Store the response in an array, 0 => response code, 1 => response message.
		$this->_currentStatusResponse = array((int) substr($response, 0, 3), (string) rtrim(substr($response, 4)));

		return $this->_currentStatusResponse[0];
	}

	/**
	 * Retrieve textural data
	 *
	 * Get data until a line with only a '.' in it is read and return data.
	 *
	 * @return mixed (array) text response on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function _getTextResponse()
	{
		// Buffer to hold the received lines.
		$data = array();

		$debug = ($this->_logger && $this->_logger->_isMasked(PEAR_LOG_DEBUG));

		// Continue until connection is lost.
		while (!feof($this->_socket)) {

			// Retrieve and append up to 1024 characters from the server.
			$line = @fgets($this->_socket, 1024);

			if ($line === false) {
				return $this->throwError('Failed to read line from socket.', null);
			}

			// Continue if the line is not terminated by CR LF.
			if (substr($line, -2) !== "\r\n" || strlen($line) < 2) {
				continue;
			}

			// Remove CR LF from the end of the line.
			$line = substr($line, 0, -2);

			// Check if the line terminates the text response.
			if ($line === '.') {

				if ($this->_logger) {
					$this->_logger->debug('T: ' . $line);
				}

				// Return all previous lines.
				return $data;
			}

			// If 1st char is '.' it's doubled (NNTP/RFC977 2.4.1).
			if (substr($line, 0, 2) === '..') {
				$line = substr($line, 1);
			}

			if ($debug) {
				$this->_logger->debug('T: ' . $line);
			}

			// Add the line to the array of lines.
			$data[] = $line;
		}

		if ($this->_logger) {
			$this->_logger->warning('Broke out of reception loop! This souldn\'t happen unless connection has been lost?');
		}

		return $this->throwError('End of stream! Connection lost?', null);
	}

	/**
	 * Send an article to usenet.
	 *
	 * @note Data should be in the format specified by RFC850.
	 *
	 * @access private
	 */
	private function _sendArticle($article)
	{
		switch (true) {
			case is_string($article):
				@fwrite($this->_socket, $article);
				@fwrite($this->_socket, "\r\n.\r\n");

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

	/**
	 * Return the last received response message.
	 *
	 * @return string The response message.
	 * @access private
	 */
	private function _currentStatusResponse()
	{
		return $this->_currentStatusResponse[1];
	}

	/* Session administration commands */

	/**
	 * Connect to a NNTP server
	 *
	 * @param string $host          (optional) The address of the NNTP-server to connect to, defaults to 'localhost'.
	 * @param mixed  $encryption    (optional) Use TLS/SSL on the connection?
	 *                              (string) 'tcp'                 => Use no encryption.
	 *                                       'ssl', 'sslv3', 'tls' => Use encryption.
	 *                              (null)|(false) Use no encryption.
	 * @param int    $port          (optional) The port number to connect to, defaults to 119.
	 * @param int    $timeout       (optional) How many seconds to wait before giving up when connecting.
	 * @param int    $socketTimeout (optional) How many seconds to wait before timing out the (blocked) socket.
	 *
	 * @return mixed (bool)   On success: True when posting allowed, otherwise false.
	 *               (object) On failure: pear_error
	 * @access protected
	 */
	protected function connect($host = null, $encryption = null, $port = null, $timeout = 15, $socketTimeout = 120)
	{
		if ($this->_isConnected() ) {
			return $this->throwError('Already connected, disconnect first!', null);
		}

		// v1.0.x API
		if (is_int($encryption)) {
			trigger_error('You are using deprecated API v1.0 in Net_NNTP_Protocol_Client: connect() !', E_USER_NOTICE);
			$port = $encryption;
			$encryption = false;
		}

		if (is_null($host)) {
			$host = 'localhost';
		}

		// Choose transport based on encryption, and if no port is given, use default for that encryption.
		switch ($encryption) {
			case null:
			case 'tcp':
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
				$message = '$encryption parameter must be either tcp, tls, ssl.';
				trigger_error($message, E_USER_ERROR);
				return $this->throwError($message);
		}

		// Attempt to connect to usenet.
		$socket = stream_socket_client(
			$transport . '://' . $host . ':' . $port,
			$errorNumber, $errorString, $timeout, STREAM_CLIENT_CONNECT,
			stream_context_create(nzedb\utility\Utility::streamSslContextOptions())
		);

		if ($socket === false) {

			$message = "Connection to $transport://$host:$port failed.";

			if (preg_match('/tls|ssl/', $transport)) {
				$message .= ' Try disabling SSL/TLS, and/or try a different port.';
			}

			$message .= ' [ERROR ' . $errorNumber . ': ' . $errorString . ']';

			if ($this->_logger) {
				$this->_logger->notice($message);
			}
			return $this->throwError($message);
		}

		// Store the socket resource as property.
		$this->_socket = $socket;

		$this->_socketTimeout = (is_numeric($socketTimeout) ? $socketTimeout : $this->_socketTimeout);

		// Set the socket timeout.
		stream_set_timeout($this->_socket, $this->_socketTimeout);

		if ($this->_logger) {
			$this->_logger->info("Connection to $transport://$host:$port has been established.");
		}

		// Retrieve the server's initial response.
		$response = $this->_getStatusResponse();
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 200, Posting allowed
			case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED:
				// TODO: Set some variable before return
				return true;

			 // 201, Posting NOT allowed
			case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_PROHIBITED:
				if ($this->_logger) {
					$this->_logger->info('Posting not allowed!');
				}

				// TODO: Set some variable before return
				return false;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Returns servers capabilities
	 *
	 * @return mixed (array)  list of capabilities on success or
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdCapabilities()
	{
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

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 *
	 *
	 * @return mixed (bool)   true when posting allowed, false when  posting disallowed
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdModeReader()
	{
		$response = $this->_sendCommand('MODE READER');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 200, RFC2980: 'Hello, you can post'
			case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED:
				// TODO: Set some variable before return
				return true;

			// 201, RFC2980: 'Hello, you can't post'
			case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_PROHIBITED:
				if ($this->_logger) {
					$this->_logger->info('Posting not allowed!');
				}
				// TODO: Set some variable before return
				return false;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Alias for cmdQuit()
	 *
	 * @access protected
	 */
	protected function disconnect()
	{
		return $this->cmdQuit();
	}

	/**
	 * Disconnect from the NNTP server
	 *
	 * @return mixed (bool)   true on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdQuit()
	{
		$response = $this->_sendCommand('QUIT');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// RFC977: 'closing connection - goodbye!'
			case NET_NNTP_PROTOCOL_RESPONSECODE_DISCONNECTING_REQUESTED:
				// If socket is still open, close it.
				$disconnected = true;
				if ($this->_isConnected(false)) {
					$disconnected = (bool)stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);
				}

				if ($this->_logger) {
					$this->_logger->info('Connection closed.');
				}
				$this->_currentStatusResponse = null;
				$this->_socket = null;
				return $disconnected;

			default:
				return $this->_handleErrorResponse($response);
		}
	}
	/**
	 * Request TLS encryption to the news server.
	 *
	 * @return mixed (bool) on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdStartTLS()
	{
		$response = $this->_sendCommand('STARTTLS');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// RFC4642: 'continue with TLS negotiation'
			case NET_NNTP_PROTOCOL_RESPONSECODE_TLS_AUTHENTICATION_CONTINUE:
				$encrypted = stream_socket_enable_crypto($this->_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
				switch (true) {
					case $encrypted === true:
						if ($this->_logger) {
							$this->_logger->info('TLS encryption started.');
						}
						return true;

					case $encrypted === false:
						if ($this->_logger) {
							$this->_logger->info('TLS encryption failed.');
						}
						return $this->throwError('Could not initiate TLS negotiation', $response, $this->_currentStatusResponse());

					case is_int($encrypted):
						return $this->throwError('TLS encryption failed.', $response, $this->_currentStatusResponse());

					default:
						return $this->throwError(
							'Internal error - unknown response from stream_socket_enable_crypto()',
							$response,
							$this->_currentStatusResponse()
						);
				}

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/* Group and article selection */

	/**
	 * Selects a news group (issue a GROUP command to the server)
	 *
	 * @param string $newsgroup The newsgroup name
	 *
	 * @return mixed (array)  group info on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdGroup($newsgroup)
	{
		$response = $this->_sendCommand('GROUP '.$newsgroup);
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 211, RFC977: 'n f l s group selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_GROUP_SELECTED:
				$response_arr = explode(' ', trim($this->_currentStatusResponse()));

				if ($this->_logger) {
					$this->_logger->info('Group selected: ' . $response_arr[3]);
				}

				return array(
					'group' => $response_arr[3],
					'first' => $response_arr[1],
					'last'  => $response_arr[2],
					'count' => $response_arr[0]
				);

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Gets a group overview.
	 *
	 * @param string $newsgroup (optional)
	 * @param mixed  $range     (optional)
	 *
	 * @return mixed (array)             On success
	 *               (object) pear_error On failure
	 * @access protected
	 */
	protected function cmdListgroup($newsgroup = null, $range = null)
	{
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

				$response_arr = explode(' ', trim($this->_currentStatusResponse()), 4);

				// If server does not return group summary in status response, return null array.
				if (!is_numeric($response_arr[0]) ||
					!is_numeric($response_arr[1]) ||
					!is_numeric($response_arr[2]) ||
					empty($response_arr[3])
				) {
					return array(
						'group'    => null,
						'first'    => null,
						'last'     => null,
						'count'    => null,
						'articles' => $articles
					);
				}

				return array(
					'group'    => $response_arr[3],
					'first'    => $response_arr[1],
					'last'     => $response_arr[2],
					'count'    => $response_arr[0],
					'articles' => $articles
				);

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Select the previous article in current group.
	 *
	 * @return mixed (array) or (string) or (int) On success
	 *               (object) pear_error          On failure
	 * @access protected
	 */
	protected function cmdLast()
	{
		$response = $this->_sendCommand('LAST');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 223, RFC977: 'n a article retrieved - request text separately (n = article number, a = unique article id)'
			case NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED:
				$response_arr = explode(' ', trim($this->_currentStatusResponse()));

				if ($this->_logger) {
					$this->_logger->info('Selected previous article: ' . $response_arr[0] .' - '. $response_arr[1]);
				}

				return array($response_arr[0], (string) $response_arr[1]);

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Select the next article in current group.
	 *
	 * @return mixed (array) or (string) or (int) On success
	 *               (object) pear_error          On failure
	 * @access protected
	 */
	protected function cmdNext()
	{
		$response = $this->_sendCommand('NEXT');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 223, RFC977: 'n a article retrieved - request text separately (n = article number, a = unique article id)'
			case NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED:
				$response_arr = explode(' ',
					trim($this->_currentStatusResponse()));

				if ($this->_logger) {
					$this->_logger->info('Selected previous article: ' . $response_arr[0] .' - '. $response_arr[1]);
				}

				return array($response_arr[0], (string) $response_arr[1]);

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/* Retrieval of articles and article sections */

	/**
	 * Get an article from the currently open connection.
	 *
	 * @param mixed $article Either a message-id or a message-number of the article to fetch.
	 *                       If null or '', then use current article.
	 *
	 * @return mixed (array) article on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdArticle($article = null)
	{
		if (is_null($article)) {
			$command = 'ARTICLE';
		} else {
			$command = 'ARTICLE ' . $article;
		}

		$response = $this->_sendCommand($command);
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 220, RFC977: 'n <a> article retrieved - head and body follow (n = article number, <a> = message-id)'
			case NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_FOLLOWS:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				if ($this->_logger) {
					$this->_logger->info(($article == null ? 'Fetched current article' : 'Fetched article: '. $article));
				}
				return $data;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Get the headers of an article from the currently open connection.
	 *
	 * @param mixed $article Either a message-id or a message-number of the article to fetch the headers from.
	 *                       If null or '', then use current article.
	 *
	 * @return mixed (array)  headers on success or
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdHead($article = null)
	{
		if (is_null($article)) {
			$command = 'HEAD';
		} else {
			$command = 'HEAD ' . $article;
		}

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
					$this->_logger->info(($article == null ? 'Fetched current article header' : 'Fetched article header for article: '.$article));
				}

				return $data;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Get the body of an article from the currently open connection.
	 *
	 * @param mixed $article Either a message-id or a message-number of  the article to fetch the body from.
	 *                       If null or '', then use current article.
	 *
	 * @return mixed (array) body on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdBody($article = null)
	{
		if (is_null($article)) {
			$command = 'BODY';
		} else {
			$command = 'BODY ' . $article;
		}

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
					$this->_logger->info(($article == null ? 'Fetched current article body' : 'Fetched article body for article: '.$article));
				}

				return $data;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Selects an article by article message-number.
	 *
	 * @param mixed $article
	 *
	 * @return mixed (array) or (string) or (int) on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdStat($article = null)
	{
		if (is_null($article)) {
			$command = 'STAT';
		} else {
			$command = 'STAT ' . $article;
		}

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
					$this->_logger->info('Selected article: ' . $response_arr[0].' - '.$response_arr[1]);
				}

				return array($response_arr[0], (string) $response_arr[1]);

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/* Article posting */

	/**
	 * Post an article to a newsgroup.
	 *
	 * @return mixed (bool) true on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdPost()
	{
		$response = $this->_sendCommand('POST');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 340, RFC977: 'send article to be posted. End with <CR-LF>.<CR-LF>'
			case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SEND:
				return true;

			default:
				return $this->_handleErrorResponse($response);
		}

	}

	/**
	 * Post an article to a newsgroup.
	 *
	 * @note Should be presented in the format specified by RFC850.
	 *
	 * @param mixed $article (string/array)
	 *
	 * @return mixed (bool) true on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdPost2($article)
	{
		$this->_sendArticle($article);

		// Retrieve server's response.
		$response = $this->_getStatusResponse();
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 240, RFC977: 'article posted ok'
			case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SUCCESS:
				return true;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Tell the news server we want to upload an article, the server will check if it has the message-id first.
	 *
	 * @param string $id Message-ID.
	 *
	 * @return mixed (bool) true on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdIhave($id)
	{
		$response = $this->_sendCommand('IHAVE ' . $id);
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 335, RFC997: 'Send article to be transferred'
			case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SEND:
				return true;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Tell the news server we have an article to upload, the server will check if it has it first.
	 *
	 * @note Should be presented in the format specified by RFC850.
	 *
	 * @param mixed $article (string/array)
	 *
	 * @return mixed (bool) true on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdIhave2($article)
	{
		$this->_sendArticle($article);

		// Retrieve server's response.
		$response = $this->_getStatusResponse();
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 235, RFC977: 'Article transferred OK'
			case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SUCCESS:
				return true;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/* Information commands */

	/**
	 * Get the date from the news server.
	 *
	 * @return mixed (string) or (int) 'YYYYMMDDhhmmss', timestamp on success
	 *                        (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdDate()
	{
		$response = $this->_sendCommand('DATE');
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// 111, RFC2980: '(string of numbers representing the date and time)'
			case NET_NNTP_PROTOCOL_RESPONSECODE_SERVER_DATE:
				return $this->_currentStatusResponse();

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Returns the server's help text.
	 *
	 * @return mixed (array) help text on success or
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdHelp()
	{
		$response = $this->_sendCommand('HELP');
		if ($this->isError($response)) {
			return $response;
		}

		switch ($response) {
			// 100, RFC977: 'Help text follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_HELP_FOLLOWS:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}
				return $data;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Fetches a list of all newsgroups created since a specified date.
	 *
	 * @param int    $time          Last time you checked for groups (timestamp).
	 * @param string $distributions (optional) (deprecaded in rfc draft)
	 *
	 * @return mixed (array) nested array with informations about existing newsgroups on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdNewgroups($time, $distributions = null)
	{
		$date = gmdate('ymd His', $time);

		if (is_null($distributions)) {
			$command = 'NEWGROUPS ' . $date . ' GMT';
		} else {
			$command = 'NEWGROUPS ' . $date . ' GMT <' . $distributions . '>';
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

					$group = array(
						'group'   => $arr[0],
						'last'    => $arr[1],
						'first'   => $arr[2],
						'posting' => $arr[3]
					);

					$groups[$group['group']] = $group;
				}
				return $groups;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 *
	 *
	 * @param int   $time         Unix timestamp.
	 * @param mixed $newsgroups   (string or array of strings)
	 * @param mixed $distribution (string or array of strings)
	 *
	 * @return mixed
	 * @access protected
	 */
	protected function cmdNewnews($time, $newsgroups, $distribution = null)
	{
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

			$command = 'NEWNEWS ' . $newsgroups . ' ' . $date . ' GMT <' . $distribution . '>';
		}

		// TODO: the length of the request string may not exceed 510 chars.

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

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/* The LIST commands */

	/**
	 * Fetches a list of all available newsgroups.
	 *
	 * @return mixed (array) nested array with informations about existing newsgroups on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdList()
	{
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

					$group = array(
						'group'   => $arr[0],
						'last'    => $arr[1],
						'first'   => $arr[2],
						'posting' => $arr[3]
					);

					$groups[$group['group']] = $group;
				}
				return $groups;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Fetches a list of all available newsgroups.
	 *
	 * @param string $wildMat
	 *
	 * @return mixed (array)  nested array with informations about existing newsgroups on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdListActive($wildMat = null)
	{
		if (is_null($wildMat)) {
			$command = 'LIST ACTIVE';
		} else {
			$command = 'LIST ACTIVE ' . $wildMat;
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

					$group = array(
						'group'   => $arr[0],
						'last'    => $arr[1],
						'first'   => $arr[2],
						'posting' => $arr[3]
					);

					$groups[$group['group']] = $group;
				}

				if ($this->_logger) {
					$this->_logger->info('Fetched list of available groups');
				}

				return $groups;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Fetches a list of (all) avaible newsgroup descriptions.
	 *
	 * @param string $wildMat Wildmat of the groups, that is to be listed, defaults to null;
	 *
	 * @return mixed (array)  nested array with description of existing newsgroups on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdListNewsgroups($wildMat = null)
	{
		if (is_null($wildMat)) {
			$command = 'LIST NEWSGROUPS';
		} else {
			$command = 'LIST NEWSGROUPS ' . $wildMat;
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
					if (preg_match('/^(\S+)\s+(.*)$/', ltrim($line), $matches)) {
						$groups[$matches[1]] = (string) $matches[2];
					} else if ($this->_logger) {
						$this->_logger->warning("Recieved non-standard line: '$line'");
					}
				}

				if ($this->_logger) {
					$this->_logger->info('Fetched group descriptions');
				}

				return $groups;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/* Article field access commands */

	/**
	 * Fetch message header from message number $first until $last
	 *
	 * @info The format of the returned array is: $messages[][header_name]
	 *
	 * @param string $range (optional) articles to fetch
	 *
	 * @return mixed (array) nested array of message and there headers on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdOver($range = null)
	{
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
					$this->_logger->info('Fetched overview ' . ($range == null ? 'for current article' : 'for range: ' . $range));
				}

				return $data;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Fetch message header from message number $first until $last
	 *
	 * @info The format of the returned array is: $messages[message_id][header_name]
	 *
	 * @param string $range (optional) articles to fetch
	 *
	 * @return mixed (array) nested array of message and there headers on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdXOver($range = null)
	{
		// Deprecated API (the code _is_ still in alpha state).
		if (func_num_args() > 1) {
			exit('The second parameter in cmdXOver() has been deprecated! Use x-y instead...');
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
					$this->_logger->info('Fetched overview ' . ($range == null ? 'for current article' : 'for range: ' . $range));
				}

				return $data;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Returns a list of available headers which are sent from tge news server to the client for every news message.
	 *
	 * @return mixed (array) of header names on success or
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdListOverviewFmt()
	{
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

					// Check if post fixed by ':full' (case-insensitive)
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

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 *
	 *
	 * The format of the returned array is:
	 * $messages[message_id]
	 *
	 * @param string $field (optional)
	 * @param string $range (optional) articles to fetch
	 *
	 * @return mixed (array) nested array of message and there header on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdXHdr($field, $range = null)
	{
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
			case NET_NNTP_PROTOCOL_RESPONSECODE_HEAD_FOLLOWS:
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

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Fetches a list of (all) available newsgroup descriptions.
	 * Deprecated as of RFC2980.
	 *
	 * @param string $wildMat Wildmat of the groups, that is to be listed, defaults to '*';
	 *
	 * @return mixed (array) nested array with description of existing newsgroups on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdXGTitle($wildMat = '*')
	{
		$response = $this->_sendCommand('XGTITLE '.$wildMat);
		if ($this->isError($response)){
			return $response;
		}

		switch ($response) {
			// RFC2980: 'list of groups and descriptions follows'
			case NET_NNTP_PROTOCOL_RESPONSECODE_XGTITLE_GROUPS_FOLLOW:
				$data = $this->_getTextResponse();
				if ($this->isError($data)) {
					return $data;
				}

				$groups = array();

				foreach($data as $line) {
					preg_match('/^(.*?)\s(.*?$)/', trim($line), $matches);
					$groups[$matches[1]] = (string) $matches[2];
				}

				return $groups;

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Fetch message references from message number $first to $last
	 *
	 * @param string $range (optional) articles to fetch
	 *
	 * @return mixed (array) assoc. array of message references on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdXROver($range = null)
	{
		// Warn about deprecated API (the code _is_ still in alpha state)
		if (func_num_args() > 1 ) {
			exit('The second parameter in cmdXROver() has been deprecated! Use x-y instead...');
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

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 *
	 *
	 * @param string $field
	 * @param string $range
	 * @param mixed $wildMat
	 *
	 * @return mixed (array)  nested array of message and there headers on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdXPat($field, $range, $wildMat)
	{
		if (is_array($wildMat)) {
		$wildMat = implode(' ', $wildMat);
		}

		$response = $this->_sendCommand('XPAT ' . $field . ' ' . $range . ' ' . $wildMat);
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

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Authenticate using 'original' method
	 *
	 * @param string $user The username to authenticate as.
	 * @param string $pass The password to authenticate with.
	 *
	 * @return mixed (bool) true on success or
	 *               (object) pear_error on failure
	 *
	 * @access protected
	 */
	protected function cmdAuthinfo($user, $pass) {
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

			default:
				return $this->_handleErrorResponse($response);
		}
	}

	/**
	 * Authenticate using 'simple' method
	 *
	 * @param string $user The username to authenticate as.
	 * @param string $pass The password to authenticate with.
	 *
	 * @return mixed (bool) true on success or (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdAuthinfoSimple($user, $pass) {
		return $this->throwError("The auth mode: 'simple' is has not been implemented yet", null);
	}

	/**
	 * Authenticate using 'generic' method
	 *
	 * @param string $user The username to authenticate as.
	 * @param string $pass The password to authenticate with.
	 *
	 * @return mixed (bool)   true on success
	 *               (object) pear_error on failure
	 * @access protected
	 */
	protected function cmdAuthinfoGeneric($user, $pass) {
		return $this->throwError("The auth mode: 'generic' is has not been implemented yet", null);
	}

	/**
	 * Test whether we are connected or not.
	 *
	 * @param bool $feof Check for the end of file pointer.
	 *
	 * @return bool true or false
	 * @access protected
	 */
	protected function _isConnected($feof = true)
	{
		return (is_resource($this->_socket) && ($feof ? !feof($this->_socket) : true));
	}

	/**
	 * Verify NNTP error code and return PEAR error.
	 *
	 * @param int $response NET_NNTP Response code
	 *
	 * @return object PEAR error
	 * @access protected
	 */
	protected function _handleErrorResponse(&$response)
	{
		switch ($response) {

			// 381, RFC2980: 'More authentication information required'
			case NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_CONTINUE:
				return $this->throwError('More authentication information required', $response, $this->_currentStatusResponse());

			// 400, RFC977: 'Service discontinued'
			case NET_NNTP_PROTOCOL_RESPONSECODE_DISCONNECTING_FORCED:
				return $this->throwError('Server refused connection', $response, $this->_currentStatusResponse());

			// 411, RFC977: 'no such news group'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_GROUP:
				return $this->throwError('No such news group on server', $response, $this->_currentStatusResponse());

			// 412, RFC2980: 'No news group current selected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED:
				return $this->throwError('No news group current selected', $response, $this->_currentStatusResponse());

			// 420, RFC2980: 'Current article number is invalid'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED:
				return $this->throwError('Current article number is invalid', $response, $this->_currentStatusResponse());

			// 421, RFC977: 'no next article in this group'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_NEXT_ARTICLE:
				return $this->throwError('No next article in this group', $response, $this->_currentStatusResponse());

			// 422, RFC977: 'no previous article in this group'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_PREVIOUS_ARTICLE:
				return $this->throwError('No previous article in this group', $response, $this->_currentStatusResponse());

			// 423, RFC977: 'No such article number in this group'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER:
				return $this->throwError('No such article number in this group', $response, $this->_currentStatusResponse());

			// 430, RFC977: 'No such article found'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID:
				return $this->throwError('No such article found', $response, $this->_currentStatusResponse());

			// 435, RFC977: 'Article not wanted'
			case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_UNWANTED:
				return $this->throwError('Article not wanted', $response, $this->_currentStatusResponse());

			// 436, RFC977: 'Transfer failed - try again later'
			case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_FAILURE:
				return $this->throwError('Transfer failed - try again later', $response, $this->_currentStatusResponse());

			// 437, RFC977: 'Article rejected - do not try again'
			case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_REJECTED:
				return $this->throwError('Article rejected - do not try again', $response, $this->_currentStatusResponse());

			// 440, RFC977: 'posting not allowed'
			case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_PROHIBITED:
				return $this->throwError('Posting not allowed', $response, $this->_currentStatusResponse());

			// 441, RFC977: 'posting failed'
			case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_FAILURE:
				return $this->throwError('Posting failed', $response, $this->_currentStatusResponse());

			// 481, RFC2980: 'Groups and descriptions unavailable'
			case NET_NNTP_PROTOCOL_RESPONSECODE_XGTITLE_GROUPS_UNAVAILABLE:
				return $this->throwError('Groups and descriptions unavailable', $response, $this->_currentStatusResponse());

			// 482, RFC2980: 'Authentication rejected'
			case NET_NNTP_PROTOCOL_RESPONSECODE_AUTHENTICATION_REJECTED:
				return $this->throwError('Authentication rejected', $response, $this->_currentStatusResponse());

			// 500, RFC977: 'Command not recognized'
			case NET_NNTP_PROTOCOL_RESPONSECODE_UNKNOWN_COMMAND:
				return $this->throwError('Command not recognized', $response, $this->_currentStatusResponse());

			// 501, RFC977: 'Command syntax error'
			case NET_NNTP_PROTOCOL_RESPONSECODE_SYNTAX_ERROR:
				return $this->throwError('Command syntax error', $response, $this->_currentStatusResponse());

			// 502, RFC2980: 'No permission'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NOT_PERMITTED:
				return $this->throwError('No permission', $response, $this->_currentStatusResponse());

			// 503, RFC2980: 'Program fault - command not performed'
			case NET_NNTP_PROTOCOL_RESPONSECODE_NOT_SUPPORTED:
				return $this->throwError('Internal server error, function not performed', $response, $this->_currentStatusResponse());

			// 580, RFC4642: 'Can not initiate TLS negotiation'
			case NET_NNTP_PROTOCOL_RESPONSECODE_TLS_FAILED_NEGOTIATION:
				return $this->throwError('Can not initiate TLS negotiation', $response, $this->_currentStatusResponse());

			default:
				$text = $this->_currentStatusResponse();
				return $this->throwError("Unexpected response: '$text'", $response, $text);
		}
	}

}