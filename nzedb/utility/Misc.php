<?php
namespace nzedb\utility;

use nzedb\ColorCLI;
use nzedb\db\Settings;


/*
 * General util functions.
 * Class Util
 */
class Misc
{
	/**
	 *  Regex for detecting multi-platform path. Use it where needed so it can be updated in one location as required characters get added.
	 */
	const PATH_REGEX = '(?P<drive>[A-Za-z]:|)(?P<path>[\\/\w .-]+|)';

	/**
	 * Checks all levels of the supplied path are readable and executable by current user.
	 *
	 * @todo Make this recursive with a switch to only check end point.
	 * @param $path	*nix path to directory or file
	 *
	 * @return bool|string True is successful, otherwise the part of the path that failed testing.
	 */
	public static function canExecuteRead($path)
	{
		$paths = preg_split('#/#', $path);
		$fullPath = DS;
		foreach ($paths as $path) {
			if ($path !== '') {
				$fullPath .= $path . DS;
				if (!is_readable($fullPath) || !is_executable($fullPath)) {
					return "The '$fullPath' directory must be readable and executable by all ." .PHP_EOL;
				}
			}
		}
		return true;
	}

	public static function clearScreen()
	{
		if (self::isCLI()) {
			if (self::isWin()) {
				passthru('cls');
			} else {
				passthru('clear');
			}
		}
	}

	/**
	 * Set curl context options for verifying SSL certificates.
	 *
	 * @param bool $verify false = Ignore config.php and do not verify the openssl cert.
	 *                     true  = Check config.php and verify based on those settings.
	 *                     If you know the certificate will be self-signed, pass false.
	 *
	 * @return array
	 * @static
	 * @access public
	 */
	public static function curlSslContextOptions($verify = true)
	{
		$options = [];
		if ($verify && nZEDb_SSL_VERIFY_HOST && (!empty(nZEDb_SSL_CAFILE) || !empty(nZEDb_SSL_CAPATH))) {
			$options += [
				CURLOPT_SSL_VERIFYPEER => (bool)nZEDb_SSL_VERIFY_PEER,
				CURLOPT_SSL_VERIFYHOST => (nZEDb_SSL_VERIFY_HOST ? 2 : 0),
			];
			if (!empty(nZEDb_SSL_CAFILE)) {
				$options += [CURLOPT_CAINFO => nZEDb_SSL_CAFILE];
			}
			if (!empty(nZEDb_SSL_CAPATH)) {
				$options += [CURLOPT_CAPATH => nZEDb_SSL_CAPATH];
			}
		} else {
			$options += [
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => 0,
			];
		}

		return $options;
	}

	public static function  getCoverURL(array $options = [])
	{
		$defaults = [
			'id' => null,
			'suffix' => '-cover.jpg',
			'type' => '',
		];
		$options += $defaults;
		$fileSpecTemplate = '%s/%s%s';
		$fileSpec = '';

		if (!empty($options['id']) && in_array($options['type'],
		   ['anime', 'audio', 'audiosample', 'book', 'console', 'games', 'movies', 'music', 'preview', 'sample', 'tvrage', 'video', 'xxx'])) {
			$fileSpec = sprintf($fileSpecTemplate, $options['type'], $options['id'], $options['suffix']);
			$fileSpec = file_exists(nZEDb_COVERS . $fileSpec) ? $fileSpec : sprintf($fileSpecTemplate, $options['type'], 'no', $options['suffix']);
		}

		return $fileSpec;
	}


	/**
	 * Get list of files/directories from supplied directory.
	 *
	 * @param array $options
	 *        'dir'        => boolean, include directory paths
	 *        'ext'        => file suffix, no full stop (period) separator should be used.
	 *        'path'    => The path to list from. If left empty it will use whatever the current working directory is.
	 *        'regex'    => Regular expressions that the full path must match to be included,
	 *
	 * @return array    Always returns array of path-names in unix format (even on Windows).
	 */
	public static function getDirFiles(array $options = null)
	{
		$defaults = [
			'dir'   => false,
			'ext'   => '',
			'path'  => '',
			'regex' => '',
		];
		$options += $defaults;

		// Replace windows style path separators with unix style.
		$iterator = new \FilesystemIterator(
			str_replace('\\', '/', $options['path']),
			\FilesystemIterator::KEY_AS_PATHNAME |
			\FilesystemIterator::SKIP_DOTS |
			\FilesystemIterator::UNIX_PATHS
		);

		$files = [];
		foreach ($iterator as $fileInfo) {
			$file = $iterator->key();
			switch (true) {
				case !$options['dir'] && $fileInfo->isDir():
					break;
				case !empty($options['ext']) && $fileInfo->getExtension() != $options['ext'];
					break;
				case (empty($options['regex']) || !preg_match($options['regex'], $file)):
					break;
				default:
					$files[] = $file;
			}
		}

		return $files;
	}

	/**
	 * Use cURL To download a web page into a string.
	 *
	 * @param array $options See details below.
	 *
	 * @return bool|mixed
	 * @access public
	 * @static
	 */
	public static function getUrl(array $options = [])
	{
		$defaults = [
			'url'            => '',    // String ; The URL to download.
			'method'         => 'get', // String ; Http method, get/post/etc..
			'postdata'       => '',    // String ; Data to send on post method.
			'language'       => '',    // String ; Language in request header string.
			'debug'          => false, // Bool   ; Show curl debug information.
			'useragent'      => '',    // String ; User agent string.
			'cookie'         => '',    // String ; Cookie string.
			'requestheaders' => [],    // Array  ; List of request headers.
			                           //          Example: ["Content-Type: application/json", "DNT: 1"]
			'verifycert'     => true,  // Bool   ; Verify certificate authenticity?
			                           //          Since curl does not have a verify self signed certs option,
			                           //          you should use this instead if your cert is self signed.
		];

		$options += $defaults;

		if (!$options['url']) {
			return false;
		}

		switch ($options['language']) {
			case 'fr':
			case 'fr-fr':
				$options['language'] = "fr-fr";
				break;
			case 'de':
			case 'de-de':
				$options['language'] = "de-de";
				break;
			case 'en-us':
				$options['language'] = "en-us";
				break;
			case 'en-gb':
				$options['language'] = "en-gb";
				break;
			case '':
			case 'en':
			default:
				$options['language'] = 'en';
		}
		$header[] = "Accept-Language: " . $options['language'];
		if (is_array($options['requestheaders'])) {
			$header += $options['requestheaders'];
		}

		$ch = curl_init();

		$context = [
			CURLOPT_URL            => $options['url'],
			CURLOPT_HTTPHEADER     => $header,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_TIMEOUT        => 15
		];
		$context += self::curlSslContextOptions($options['verifycert']);
		if (!empty($options['useragent'])) {
			$context += [CURLOPT_USERAGENT => $options['useragent']];
		}
		if (!empty($options['cookie'])) {
			$context += [CURLOPT_COOKIE => $options['cookie']];
		}
		if ($options['method'] === 'post') {
			$context += [
				CURLOPT_POST       => 1,
				CURLOPT_POSTFIELDS => $options['postdata']
			];
		}
		if ($options['debug']) {
			$context += [
				CURLOPT_HEADER      => true,
				CURLINFO_HEADER_OUT => true,
				CURLOPT_NOPROGRESS  => false,
				CURLOPT_VERBOSE     => true
			];
		}
		curl_setopt_array($ch, $context);

		$buffer = curl_exec($ch);
		$err = curl_errno($ch);
		curl_close($ch);

		if ($err !== 0) {
			return false;
		} else {
			return $buffer;
		}
	}

	public static function getValidVersionsFile()
	{
		$versions = new Versions();

		return $versions->getValidVersionsFile();
	}

	/**
	 * Detect if the command is accessible on the system.
	 *
	 * @param string $cmd
	 *
	 * @return bool|null Returns true if found, false if not found, and null if which is not detected.
	 */
	public static function hasCommand($cmd)
	{
		if (HAS_WHICH) {
			$returnVal = shell_exec("which $cmd");

			return (empty($returnVal) ? false : true);
		} else {
			return null;
		}
	}

	/**
	 * Check for availability of which command
	 */
	public static function hasWhich()
	{
		exec('which which', $output, $error);

		return !$error;
	}

	/**
	 * Check if user is running from CLI.
	 *
	 * @return bool
	 */
	public static function isCLI()
	{
		return ((strtolower(PHP_SAPI) === 'cli') ? true : false);
	}

	public static function isGZipped($filename)
	{
		$gzipped = null;
		if (($fp = fopen($filename, 'r')) !== false) {
			if (@fread($fp, 2) == "\x1F\x8B") {
				// this is a gzip'd file
				fseek($fp, -4, SEEK_END);
				if (strlen($datum = @fread($fp, 4)) == 4) {
					$gzipped = $datum;
				}
			}
			fclose($fp);
		}

		return ($gzipped);
	}

	public static function isPatched(Settings $pdo = null)
	{
		$versions = self::getValidVersionsFile();

		if (!($pdo instanceof Settings)) {
			$pdo = new Settings();
		}
		$patch = $pdo->getSetting(['section' => '', 'subsection' => '', 'name' => 'sqlpatch']);
		$ver = $versions->versions->sql->file;

		// Check database patch version
		if ($patch < $ver) {
			$message = "\nYour database is not up to date. Reported patch levels\n   Db: $patch\nfile: $ver\nPlease update.\n php " .
				nZEDb_ROOT . "cli/update_db.php true\n";
			if (self::isCLI()) {
				echo (new ColorCLI())->error($message);
			}
			throw new \RuntimeException($message);
		}

		return true;
	}

	public static function isWin()
	{
		return (strtolower(substr(PHP_OS, 0, 3)) === 'win');
	}

	public static function setCoversConstant($path)
	{
		if (!defined('nZEDb_COVERS')) {
			switch (true) {
				case (substr($path, 0, 1) == '/' ||
					substr($path, 1, 1) == ':' ||
					substr($path, 0, 1) == '\\'):
					define('nZEDb_COVERS', Text::trailingSlash($path));
					break;
				case (strlen($path) > 0 && substr($path, 0, 1) != '/' && substr($path, 1, 1) != ':' &&
					substr($path, 0, 1) != '\\'):
					define('nZEDb_COVERS', realpath(nZEDb_ROOT . Text::trailingSlash($path)));
					break;
				case empty($path): // Default to resources location.
				default:
					define('nZEDb_COVERS', nZEDb_RES . 'covers' . DS);
			}
		}
	}

	/**
	 * Creates an array to be used with stream_context_create() to verify openssl certificates
	 * when connecting to a tls or ssl connection when using stream functions (fopen/file_get_contents/etc).
	 *
	 * @param bool $forceIgnore Force ignoring of verification.
	 *
	 * @return array
	 * @static
	 * @access public
	 */
	public static function streamSslContextOptions($forceIgnore = false)
	{
		if (empty(nZEDb_SSL_CAFILE) && empty(nZEDb_SSL_CAPATH)) {
			$options = [
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true,
			];
		} else {
			$options = [
				'verify_peer'       => ($forceIgnore ? false : (bool)nZEDb_SSL_VERIFY_PEER),
				'verify_peer_name'  => ($forceIgnore ? false : (bool)nZEDb_SSL_VERIFY_HOST),
				'allow_self_signed' => ($forceIgnore ? true : (bool)nZEDb_SSL_ALLOW_SELF_SIGNED),
			];
			if (!empty(nZEDb_SSL_CAFILE)) {
				$options['cafile'] = nZEDb_SSL_CAFILE;
			}
			if (!empty(nZEDb_SSL_CAPATH)) {
				$options['capath'] = nZEDb_SSL_CAPATH;
			}
		}
		// If we set the transport to tls and the server falls back to ssl,
		// the context options would be for tls and would not apply to ssl,
		// so set both tls and ssl context in case the server does not support tls.
		return ['tls' => $options, 'ssl' => $options];
	}

	/**
	 * Unzip a gzip file, return the output. Return false on error / empty.
	 *
	 * @param string $filePath
	 *
	 * @return string|false
	 */
	public static function unzipGzipFile($filePath)
	{
		/* Potential issues with this, so commenting out.
		$length = Misc::isGZipped($filePath);
		if ($length === false || $length === null) {
			return false;
		}*/

		$string = '';
		$gzFile = @gzopen($filePath, 'rb', 0);
		if ($gzFile) {
			while (!gzeof($gzFile)) {
				$temp = gzread($gzFile, 1024);
				// Check for empty string.
				// Without this the loop would be endless and consume 100% CPU.
				// Do not set $string empty here, as the data might still be good.
				if (!$temp) {
					break;
				}
				$string .= $temp;
			}
			gzclose($gzFile);
		}
		return ($string === '' ? false : $string);
	}

	/**
	 * Return file type/info using magic numbers.
	 * Try using `file` program where available, fallback to using PHP's finfo class.
	 *
	 * @param string $path Path to the file / folder to check.
	 *
	 * @return string File info. Empty string on failure.
	 */
	public static function fileInfo($path)
	{
		$output = '';
		$magicPath = (new Settings())->getSetting('apps.indexer.magic_file_path');
		if (self::hasCommand('file') && (!self::isWin() || !empty($magicPath))) {
			$magicSwitch = empty($magicPath) ? '' : " -m $magicPath";
			$output = self::runCmd('file' . $magicSwitch . ' -b "' . $path . '"');

			if (is_array($output)) {
				switch (count($output)) {
					case 0:
						$output = '';
						break;
					case 1:
						$output = $output[0];
						break;
					default:
						$output = implode(' ', $output);
						break;
				}
			} else {
				$output = '';
			}
		} else {
			$fileInfo = empty($magicPath) ? new \finfo(FILEINFO_RAW) : new \finfo(FILEINFO_RAW, $magicPath);

			$output = $fileInfo->file($path);
			if (empty($output)) {
				$output = '';
			}
			$fileInfo->close();
		}

		return $output;
	}

	/**
	 * Run CLI command.
	 *
	 * @param string $command
	 * @param bool   $debug
	 *
	 * @return array
	 */
	public static function runCmd($command, $debug = false)
	{
		if ($debug) {
			echo '-Running Command: ' . PHP_EOL . '   ' . $command . PHP_EOL;
		}

		$output = [];
		$status = 1;
		@exec($command, $output, $status);

		if ($debug) {
			echo '-Command Output: ' . PHP_EOL . '   ' . implode(PHP_EOL . '  ', $output) . PHP_EOL;
		}

		return $output;
	}

	/**
	 * Remove unsafe chars from a filename.
	 *
	 * @param string $filename
	 *
	 * @return string
	 */
	public static function safeFilename($filename)
	{
		return trim(preg_replace('/[^\w\s.-]*/i', '', $filename));
	}

	/**
	 * Get human readable size string from bytes.
	 *
	 * @param int $bytes     Bytes number to convert.
	 * @param int $precision How many floating point units to add.
	 *
	 * @return string
	 */
	public static function bytesToSizeString($bytes, $precision = 0)
	{
		if ($bytes == 0) {
			return '0B';
		}
		$unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];

		return round($bytes / pow(1024, ($index = floor(log($bytes, 1024)))), $precision) . $unit[(int)$index];
	}


	/**
	 * Fetches an embeddable video to a IMDB trailer from http://www.traileraddict.com
	 *
	 * @param $imdbID
	 *
	 * @return string
	 */
	public static function imdb_trailers($imdbID)
	{
		$xml = Misc::getUrl(['url' => 'http://api.traileraddict.com/?imdb=' . $imdbID]);
		if ($xml !== false) {
			if (preg_match('/(<iframe.+?<\/iframe>)/i', $xml, $html)) {
				return $html[1];
			}
		}
		return '';
	}

	// Convert obj to array.
	public static function objectsIntoArray($arrObjData, $arrSkipIndices = [])
	{
		$arrData = [];

		// If input is object, convert into array.
		if (is_object($arrObjData)) {
			$arrObjData = get_object_vars($arrObjData);
		}

		if (is_array($arrObjData)) {
			foreach ($arrObjData as $index => $value) {
				// Recursive call.
				if (is_object($value) || is_array($value)) {
					$value = self::objectsIntoArray($value, $arrSkipIndices);
				}
				if (in_array($index, $arrSkipIndices)) {
					continue;
				}
				$arrData[$index] = $value;
			}
		}
		return $arrData;
	}

	/**
	 * Central function for sending site email.
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $contents
	 * @param string $from
	 *
	 * @return boolean
	 * @throws \Exception
	 * @throws \phpmailerException
	 */
	public static function sendEmail($to, $subject, $contents, $from)
	{
		// Email *always* uses CRLF for line endings unless the mail agent is broken, like qmail
		$CRLF = "\r\n";

		// Setup the body first since we need it regardless of sending method.
		$body = '<html>' . $CRLF;
		$body .= '<body style=\'font-family:Verdana, Verdana, Geneva, sans-serif; font-size:12px; color:#666666;\'>' . $CRLF;
		$body .= $contents;
		$body .= '</body>' . $CRLF;
		$body .= '</html>' . $CRLF;

		if (defined('PHPMAILER_ENABLED') && PHPMAILER_ENABLED == true) {
			$mail = new \PHPMailer;
		} else {
			$mail = null;
		}

		// If the mailer couldn't instantiate there's a good chance the user has an incomplete update & we should fallback to php mail()
		// @todo Log this failure.
		if (!defined('PHPMAILER_ENABLED') || PHPMAILER_ENABLED !== true || !($mail instanceof \PHPMailer)) {
			$headers = 'From: ' . $from . $CRLF;
			$headers .= 'Reply-To: ' . $from . $CRLF;
			$headers .= 'Return-Path: ' . $from . $CRLF;
			$headers .= 'X-Mailer: nZEDb' . $CRLF;
			$headers .= 'MIME-Version: 1.0' . $CRLF;
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . $CRLF;
			$headers .= $CRLF;

			return mail($to, $subject, $body, $headers);
		}

		// Check to make sure the user has their settings correct.
		if (PHPMAILER_USE_SMTP == true) {
			if ((!defined('PHPMAILER_SMTP_HOST') || PHPMAILER_SMTP_HOST === '') ||
				(!defined('PHPMAILER_SMTP_PORT') || PHPMAILER_SMTP_PORT === '')
			) {
				throw new \phpmailerException(
					'You opted to use SMTP but the PHPMAILER_SMTP_HOST and/or PHPMAILER_SMTP_PORT is/are not defined correctly! Either fix the missing/incorrect values or change PHPMAILER_USE_SMTP to false in the www/settings.php'
				);
			}

			// If the user enabled SMTP & Auth but did not setup credentials, throw an exception.
			if (defined('PHPMAILER_SMTP_AUTH') && PHPMAILER_SMTP_AUTH == true) {
				if ((!defined('PHPMAILER_SMTP_USER') || PHPMAILER_SMTP_USER === '') ||
					(!defined('PHPMAILER_SMTP_PASSWORD') || PHPMAILER_SMTP_PASSWORD === '')
				) {
					throw new \phpmailerException(
						'You opted to use SMTP and SMTP Auth but the PHPMAILER_SMTP_USER and/or PHPMAILER_SMTP_PASSWORD is/are not defined correctly. Please set them in www/settings.php'
					);
				}
			}
		}

		//Finally we can send the mail.
		$mail->isHTML(true);

		if (PHPMAILER_USE_SMTP) {
			$mail->isSMTP();

			$mail->Host = PHPMAILER_SMTP_HOST;
			$mail->Port = PHPMAILER_SMTP_PORT;

			$mail->SMTPSecure = PHPMAILER_SMTP_SECURE;

			if (PHPMAILER_SMTP_AUTH) {
				$mail->SMTPAuth = true;
				$mail->Username = PHPMAILER_SMTP_USER;
				$mail->Password = PHPMAILER_SMTP_PASSWORD;
			}
		}

		$settings = new Settings();

		$fromEmail = (PHPMAILER_FROM_EMAIL == '') ? $settings->getSetting('email') : PHPMAILER_FROM_EMAIL;
		$fromName  = (PHPMAILER_FROM_NAME == '') ? $settings->getSetting('title') : PHPMAILER_FROM_NAME;
		$replyTo   = (PHPMAILER_REPLYTO == '') ? $from : PHPMAILER_REPLYTO;

		if (PHPMAILER_BCC != '') {
			$mail->addBCC(PHPMAILER_BCC);
		}

		$mail->setFrom($fromEmail, $fromName);
		$mail->addAddress($to);
		$mail->addReplyTo($replyTo);
		$mail->Subject = $subject;
		$mail->Body    = $body;
		$mail->AltBody = $mail->html2text($body, true);

		$sent = $mail->send();

		if (!$sent) {
			//@todo Log failed email send attempt.
			throw new \phpmailerException('Unable to send mail. Error: ' . $mail->ErrorInfo);
		}

		return $sent;
	}
}
