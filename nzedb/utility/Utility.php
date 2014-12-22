<?php
namespace nzedb\utility;


use \nzedb\db\Settings;


/*
 * General util functions.
 * Class Util
 */
class Utility
{
	/**
	 *  Regex for detecting multi-platform path. Use it where needed so it can be updated in one location as required characters get added.
	 */
	const PATH_REGEX = '(?P<drive>[A-Za-z]:|)(?P<path>[\\/\w .-]+|)';

	static public function clearScreen()
	{
		if (self::isCLI())
		{
			if (self::isWin())
			{
				passthru('cls');
			} else {
				passthru('clear');
			}
		}
	}

	/**
	 * Replace all white space chars for a single space.
	 *
	 * @param string $text
	 *
	 * @return string
	 *
	 * @static
	 * @access public
	 */
	static public function collapseWhiteSpace($text)
	{
		// Strip leading/trailing white space.
		return trim(
		// Replace 2 or more white space for a single space.
			preg_replace('/\s{2,}/',
				' ',
				// Replace all literal and non literal new lines and carriage returns.
				str_replace(["\n", '\n', "\r", '\r'], ' ', $text)
			)
		);
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
	static public function curlSslContextOptions($verify = true)
	{
		$options = [];
		if ($verify && nZEDb_SSL_VERIFY_HOST) {
			$options += [
				CURLOPT_CAINFO         => nZEDb_SSL_CAFILE,
				CURLOPT_CAPATH         => nZEDb_SSL_CAPATH,
				CURLOPT_SSL_VERIFYPEER => (bool)nZEDb_SSL_VERIFY_PEER,
				CURLOPT_SSL_VERIFYHOST => (nZEDb_SSL_VERIFY_HOST ? 2 : 0),
			];
		} else {
			$options += [
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => 0,
			];
		}

		return $options;
	}

	/**
	 * Removes the preceeding or proceeding portion of a string
	 * relative to the last occurrence of the specified character.
	 * The character selected may be retained or discarded.
	 *
	 * @param string $character      the character to search for.
	 * @param string $string         the string to search through.
	 * @param string $side           determines whether text to the left or the right of the character is returned.
	 *                               Options are: left, or right.
	 * @param bool   $keepCharacter  determines whether or not to keep the character.
	 *                               Options are: true, or false.
	 *
	 * @return string
	 */
	static public function cutStringUsingLast($character, $string, $side, $keepCharacter = true)
	{
		$offset = ($keepCharacter ? 1 : 0);
		$wholeLength = strlen($string);
		$rightLength = (strlen(strrchr($string, $character)) - 1);
		$leftLength = ($wholeLength - $rightLength - 1);
		switch ($side) {
			case 'left':
				$piece = substr($string, 0, ($leftLength + $offset));
				break;
			case 'right':
				$start = (0 - ($rightLength + $offset));
				$piece = substr($string, $start);
				break;
			default:
				$piece = false;
				break;
		}

		return ($piece);
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
		   ['anime', 'audio', 'audiosample', 'book', 'console',  'games', 'movies', 'music', 'preview', 'sample', 'tvrage', 'video', 'xxx'])) {
			$fileSpec = sprintf($fileSpecTemplate, $options['type'], $options['id'], $options['suffix']);
			$fileSpec = file_exists(nZEDb_COVERS . $fileSpec) ? $fileSpec :
				sprintf($fileSpecTemplate, $options['type'], 'no', $options['suffix']);
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
	static public function getDirFiles(array $options = null)
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
	static public function getUrl(array $options = [])
	{
		$defaults = [
			'url'        => '', // The URL to download.
			'method'     => 'get', // Http method, get/post/etc..
			'postdata'   => '', // Data to send on post method.
			'language'   => '', // Language in header string.
			'debug'      => false, // Show curl debug information.
			'useragent'  => '', // User agent string.
			'cookie'     => '', // Cookie string.
			'verifycert' => true, /* Verify certificate authenticity?
									  Since curl does not have a verify self signed certs option,
									  you should use this instead if your cert is self signed. */
		];

		$options += $defaults;

		if (!$options['url']) {
			return false;
		}

		switch ($options['language']) {
			case 'fr':
			case 'fr-fr':
				$language = "fr-fr";
				break;
			case 'de':
			case 'de-de':
				$language = "de-de";
				break;
			case 'en-us':
				$language = "en-us";
				break;
			case 'en-gb':
				$language = "en-gb";
				break;
			case '':
			case 'en':
			default:
				$language = 'en';
		}
		$header[] = "Accept-Language: " . $language;

		$ch = curl_init();

		$context = [
			CURLOPT_URL            => $options['url'],
			CURLOPT_HTTPHEADER     => $header,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_TIMEOUT        => 15
		];
		$context += self::curlSslContextOptions($options['verifycert']);
		if ($options['useragent'] !== '') {
			$context += [CURLOPT_USERAGENT => $options['useragent']];
		}
		if ($options['cookie'] !== '') {
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

	static public function getValidVersionsFile()
	{
		$versions = new Versions();

		return $versions->getValidVersionsFile();
	}

	/**
	 * Detect if the command is accessible on the system.
	 *
	 * @param $cmd
	 *
	 * @return bool|null Returns true if found, false if not found, and null if which is not detected.
	 */
	static public function hasCommand($cmd)
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
	static public function hasWhich()
	{
		exec('which which', $output, $error);

		return !$error;
	}

	/**
	 * Check if user is running from CLI.
	 *
	 * @return bool
	 */
	static public function isCLI()
	{
		return ((strtolower(PHP_SAPI) === 'cli') ? true : false);
	}

	static public function isPatched(Settings $pdo = null)
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
				echo (new \ColorCLI())->error($message);
			}
			throw new \RuntimeException($message);
		}

		return true;
	}

	static public function isWin()
	{
		return (strtolower(substr(PHP_OS, 0, 3)) === 'win');
	}

	static public function setCoversConstant($path)
	{
		if (!defined('nZEDb_COVERS')) {
			switch (true) {
				case (substr($path, 0, 1) == '/' ||
					substr($path, 1, 1) == ':' ||
					substr($path, 0, 1) == '\\'):
					define('nZEDb_COVERS', self::trailingSlash($path));
					break;
				case (strlen($path) > 0 && substr($path, 0, 1) != '/' && substr($path, 1, 1) != ':' &&
					substr($path, 0, 1) != '\\'):
					define('nZEDb_COVERS', realpath(nZEDb_ROOT . self::trailingSlash($path)));
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
	static public function streamSslContextOptions($forceIgnore = false)
	{
		$options = [
			'verify_peer'       => ($forceIgnore ? false : (bool)nZEDb_SSL_VERIFY_PEER),
			'verify_peer_name'  => ($forceIgnore ? false : (bool)nZEDb_SSL_VERIFY_HOST),
			'allow_self_signed' => ($forceIgnore ? true : (bool)nZEDb_SSL_ALLOW_SELF_SIGNED),
		];
		if (nZEDb_SSL_CAFILE) {
			$options['cafile'] = nZEDb_SSL_CAFILE;
		}
		if (nZEDb_SSL_CAPATH) {
			$options['capath'] = nZEDb_SSL_CAPATH;
		}
		// If we set the transport to tls and the server falls back to ssl,
		// the context options would be for tls and would not apply to ssl,
		// so set both tls and ssl context in case the server does not support tls.
		return ['tls' => $options, 'ssl' => $options];
	}

	static public function stripBOM(&$text)
	{
		$bom = pack("CCC", 0xef, 0xbb, 0xbf);
		if (0 == strncmp($text, $bom, 3)) {
			$text = substr($text, 3);
		}
	}

	/**
	 * Strips non-printing characters from a string.
	 *
	 * Operates directly on the text string, but also returns the result for situations requiring a
	 * return value (use in ternary, etc.)/
	 *
	 * @param $text        String variable to strip.
	 *
	 * @return string    The stripped variable.
	 */
	static public function stripNonPrintingChars(&$text)
	{
		$lowChars = [
			"\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07",
			"\x08", "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F",
			"\x10", "\x11", "\x12", "\x13", "\x14", "\x15", "\x16", "\x17",
			"\x18", "\x19", "\x1A", "\x1B", "\x1C", "\x1D", "\x1E", "\x1F",
		];
		$text = str_replace($lowChars, '', $text);

		return $text;
	}

	static public function trailingSlash($path)
	{
		if (substr($path, strlen($path) - 1) != '/') {
			$path .= '/';
		}

		return $path;
	}

	/**
	 * Unzip a gzip file, return the output. Return false on error / empty.
	 *
	 * @param string $filePath
	 *
	 * @return bool|string
	 */
	static public function unzipGzipFile($filePath)
	{
		// String to hold the NZB contents.
		$string = '';

		// Open the gzip file.
		$gzFile = @gzopen($filePath, 'rb', 0);
		if ($gzFile) {
			// Append the decompressed data to the string until we find the end of file pointer.
			while (!gzeof($gzFile)) {
				$string .= gzread($gzFile, 1024);
			}
			// Close the gzip file.
			gzclose($gzFile);
		}

		// Return the string.
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
	static public function fileInfo($path)
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
			$fileInfo = empty($magicPath) ? new finfo(FILEINFO_RAW) : new finfo(FILEINFO_RAW, $magicPath);

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
	static public function runCmd($command, $debug = false)
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
	static public function safeFilename($filename)
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
	static public function bytesToSizeString($bytes, $precision = 0)
	{
		if ($bytes == 0) {
			return '0B';
		}
		$unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];

		return round($bytes / pow(1024, ($index = floor(log($bytes, 1024)))), $precision) . $unit[(int)$index];
	}

	/**
	 * Convert Code page 437 chars to UTF.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	static public function cp437toUTF($str)
	{
		$out = '';
		for ($i = 0; $i < strlen($str); $i++) {
			$ch = ord($str{$i});
			switch ($ch) {
				case 128:
					$out .= 'Ç';
					break;
				case 129:
					$out .= 'ü';
					break;
				case 130:
					$out .= 'é';
					break;
				case 131:
					$out .= 'â';
					break;
				case 132:
					$out .= 'ä';
					break;
				case 133:
					$out .= 'à';
					break;
				case 134:
					$out .= 'å';
					break;
				case 135:
					$out .= 'ç';
					break;
				case 136:
					$out .= 'ê';
					break;
				case 137:
					$out .= 'ë';
					break;
				case 138:
					$out .= 'è';
					break;
				case 139:
					$out .= 'ï';
					break;
				case 140:
					$out .= 'î';
					break;
				case 141:
					$out .= 'ì';
					break;
				case 142:
					$out .= 'Ä';
					break;
				case 143:
					$out .= 'Å';
					break;
				case 144:
					$out .= 'É';
					break;
				case 145:
					$out .= 'æ';
					break;
				case 146:
					$out .= 'Æ';
					break;
				case 147:
					$out .= 'ô';
					break;
				case 148:
					$out .= 'ö';
					break;
				case 149:
					$out .= 'ò';
					break;
				case 150:
					$out .= 'û';
					break;
				case 151:
					$out .= 'ù';
					break;
				case 152:
					$out .= 'ÿ';
					break;
				case 153:
					$out .= 'Ö';
					break;
				case 154:
					$out .= 'Ü';
					break;
				case 155:
					$out .= '¢';
					break;
				case 156:
					$out .= '£';
					break;
				case 157:
					$out .= '¥';
					break;
				case 158:
					$out .= '₧';
					break;
				case 159:
					$out .= 'ƒ';
					break;
				case 160:
					$out .= 'á';
					break;
				case 161:
					$out .= 'í';
					break;
				case 162:
					$out .= 'ó';
					break;
				case 163:
					$out .= 'ú';
					break;
				case 164:
					$out .= 'ñ';
					break;
				case 165:
					$out .= 'Ñ';
					break;
				case 166:
					$out .= 'ª';
					break;
				case 167:
					$out .= 'º';
					break;
				case 168:
					$out .= '¿';
					break;
				case 169:
					$out .= '⌐';
					break;
				case 170:
					$out .= '¬';
					break;
				case 171:
					$out .= '½';
					break;
				case 172:
					$out .= '¼';
					break;
				case 173:
					$out .= '¡';
					break;
				case 174:
					$out .= '«';
					break;
				case 175:
					$out .= '»';
					break;
				case 176:
					$out .= '░';
					break;
				case 177:
					$out .= '▒';
					break;
				case 178:
					$out .= '▓';
					break;
				case 179:
					$out .= '│';
					break;
				case 180:
					$out .= '┤';
					break;
				case 181:
					$out .= '╡';
					break;
				case 182:
					$out .= '╢';
					break;
				case 183:
					$out .= '╖';
					break;
				case 184:
					$out .= '╕';
					break;
				case 185:
					$out .= '╣';
					break;
				case 186:
					$out .= '║';
					break;
				case 187:
					$out .= '╗';
					break;
				case 188:
					$out .= '╝';
					break;
				case 189:
					$out .= '╜';
					break;
				case 190:
					$out .= '╛';
					break;
				case 191:
					$out .= '┐';
					break;
				case 192:
					$out .= '└';
					break;
				case 193:
					$out .= '┴';
					break;
				case 194:
					$out .= '┬';
					break;
				case 195:
					$out .= '├';
					break;
				case 196:
					$out .= '─';
					break;
				case 197:
					$out .= '┼';
					break;
				case 198:
					$out .= '╞';
					break;
				case 199:
					$out .= '╟';
					break;
				case 200:
					$out .= '╚';
					break;
				case 201:
					$out .= '╔';
					break;
				case 202:
					$out .= '╩';
					break;
				case 203:
					$out .= '╦';
					break;
				case 204:
					$out .= '╠';
					break;
				case 205:
					$out .= '═';
					break;
				case 206:
					$out .= '╬';
					break;
				case 207:
					$out .= '╧';
					break;
				case 208:
					$out .= '╨';
					break;
				case 209:
					$out .= '╤';
					break;
				case 210:
					$out .= '╥';
					break;
				case 211:
					$out .= '╙';
					break;
				case 212:
					$out .= '╘';
					break;
				case 213:
					$out .= '╒';
					break;
				case 214:
					$out .= '╓';
					break;
				case 215:
					$out .= '╫';
					break;
				case 216:
					$out .= '╪';
					break;
				case 217:
					$out .= '┘';
					break;
				case 218:
					$out .= '┌';
					break;
				case 219:
					$out .= '█';
					break;
				case 220:
					$out .= '▄';
					break;
				case 221:
					$out .= '▌';
					break;
				case 222:
					$out .= '▐';
					break;
				case 223:
					$out .= '▀';
					break;
				case 224:
					$out .= 'α';
					break;
				case 225:
					$out .= 'ß';
					break;
				case 226:
					$out .= 'Γ';
					break;
				case 227:
					$out .= 'π';
					break;
				case 228:
					$out .= 'Σ';
					break;
				case 229:
					$out .= 'σ';
					break;
				case 230:
					$out .= 'µ';
					break;
				case 231:
					$out .= 'τ';
					break;
				case 232:
					$out .= 'Φ';
					break;
				case 233:
					$out .= 'Θ';
					break;
				case 234:
					$out .= 'Ω';
					break;
				case 235:
					$out .= 'δ';
					break;
				case 236:
					$out .= '∞';
					break;
				case 237:
					$out .= 'φ';
					break;
				case 238:
					$out .= 'ε';
					break;
				case 239:
					$out .= '∩';
					break;
				case 240:
					$out .= '≡';
					break;
				case 241:
					$out .= '±';
					break;
				case 242:
					$out .= '≥';
					break;
				case 243:
					$out .= '≤';
					break;
				case 244:
					$out .= '⌠';
					break;
				case 245:
					$out .= '⌡';
					break;
				case 246:
					$out .= '÷';
					break;
				case 247:
					$out .= '≈';
					break;
				case 248:
					$out .= '°';
					break;
				case 249:
					$out .= '∙';
					break;
				case 250:
					$out .= '·';
					break;
				case 251:
					$out .= '√';
					break;
				case 252:
					$out .= 'ⁿ';
					break;
				case 253:
					$out .= '²';
					break;
				case 254:
					$out .= '■';
					break;
				case 255:
					$out .= ' ';
					break;
				default :
					$out .= chr($ch);
			}
		}
		return $out;
	}

	/**
	 * Fetches an embeddable video to a IMDB trailer from http://www.traileraddict.com
	 *
	 * @param $imdbID
	 *
	 * @return string
	 */
	static public function imdb_trailers($imdbID)
	{
		$xml = Utility::getUrl(['url' => 'http://api.traileraddict.com/?imdb=' . $imdbID]);
		if ($xml !== false) {
			if (preg_match('/(<iframe.+?<\/iframe>)/i', $xml, $html)) {
				return $html[1];
			}
		}
		return '';
	}

	// Convert obj to array.
	static public function objectsIntoArray ($arrObjData, $arrSkipIndices = [])
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

	// Central function for sending site email.
	static public function sendEmail($to, $subject, $contents, $from)
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
