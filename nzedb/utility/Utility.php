<?php
/*
 * General util functions.
 * Class Util
 */
class Util
{
	static public function hasCommand($cmd)
	{
		if (!isWindows()) {
			$returnVal = shell_exec("which $cmd");
			return (empty($returnVal) ? false : true);
		} else {
			return null;
		}
	}

	static public function setCoversConstant($path)
	{
		if (!defined('nZEDb_COVERS')) {
			define('nZEDb_COVERS', $path == '' ? nZEDb_WWW . 'covers' . DS : $path);
		}
	}

	static public function trailingSlash(&$path)
	{
		if (substr($path, strlen($path) - 1) != '/') {
			$path .= '/';
		}
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
}

// Central function for sending site email.
function sendEmail($to, $subject, $contents, $from)
{
	if (isWindows()) {
		$n = "\r\n";
	} else {
		$n = "\n";
	}
	$body = '<html>' . $n;
	$body .= '<body style=\'font-family:Verdana, Verdana, Geneva, sans-serif; font-size:12px; color:#666666;\'>' . $n;
	$body .= $contents;
	$body .= '</body>' . $n;
	$body .= '</html>' . $n;

	$headers = 'From: ' . $from . $n;
	$headers .= 'Reply-To: ' . $from . $n;
	$headers .= 'Return-Path: ' . $from . $n;
	$headers .= 'X-Mailer: nZEDb' . $n;
	$headers .= 'MIME-Version: 1.0' . $n;
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . $n;
	$headers .= $n;

	return mail($to, $subject, $body, $headers);
}

// Check if O/S is windows.
function isWindows()
{
	return (strtolower(substr(php_uname('s'), 0, 3)) === 'win');
}

// Convert obj to array.
function objectsIntoArray($arrObjData, $arrSkipIndices = array())
{
	$arrData = array();

	// If input is object, convert into array.
	if (is_object($arrObjData)) {
		$arrObjData = get_object_vars($arrObjData);
	}

	if (is_array($arrObjData)) {
		foreach ($arrObjData as $index => $value) {
			// Recursive call.
			if (is_object($value) || is_array($value)) {
				$value = objectsIntoArray($value, $arrSkipIndices);
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
 * Remove unsafe chars from a filename.
 *
 * @param string $filename
 *
 * @return string
 */
function safeFilename($filename)
{
	return trim(preg_replace('/[^\w\s.-]*/i', '', $filename));
}

/**
 * Run CLI command.
 *
 * @param string $command
 * @param bool   $debug
 *
 * @return array
 */
function runCmd($command, $debug = false)
{
	$nl = PHP_EOL;
	if (isWindows() && strpos(phpversion(), "5.2") !== false) {
		$command = "\"" . $command . "\"";
	}

	if ($debug) {
		echo '-Running Command: ' . $nl . '   ' . $command . $nl;
	}

	$output = array();
	$status = 1;
	@exec($command, $output, $status);

	if ($debug) {
		echo '-Command Output: ' . $nl . '   ' . implode($nl . '  ', $output) . $nl;
	}

	return $output;
}

function checkStatus($code)
{
	return ($code == 0) ? true : false;
}

/**
 * Use cURL To download a web page into a string.
 *
 * @param string $url       The URL to download.
 * @param string $method    get/post
 * @param string $postdata  If using POST, post your POST data here.
 * @param string $language  Use alternate langauge in header.
 * @param bool   $debug     Show debug info.
 * @param string $userAgent User agent.
 * @param string $cookie    Cookie.
 *
 * @return bool|mixed
 */
function getUrl($url, $method = 'get', $postdata = '', $language = "", $debug = false, $userAgent = '', $cookie = '')
{
	switch($language) {
		case 'fr':
		case 'fr-fr':
			$language = "fr-fr";
			break;
		case 'de':
		case 'de-de':
			$language = "de-de";
			break;
		case 'en':
			$language = 'en';
			break;
		case '':
		case 'en-us':
		default:
			$language = "en-us";
	}
	$header[] = "Accept-Language: " . $language;

	$ch = curl_init();
	$options = array(
		CURLOPT_URL            => $url,
		CURLOPT_HTTPHEADER     => $header,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_FOLLOWLOCATION => 1,
		CURLOPT_TIMEOUT        => 15,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
	);
	curl_setopt_array($ch, $options);

	if ($userAgent !== '') {
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	}

	if ($cookie !== '') {
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}

	if ($method === 'post') {
		$options = array(
			CURLOPT_POST       => 1,
			CURLOPT_POSTFIELDS => $postdata
		);
		curl_setopt_array($ch, $options);
	}

	if ($debug) {
		$options =
			array(
			CURLOPT_HEADER      => true,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_NOPROGRESS  => false,
			CURLOPT_VERBOSE     => true
		);
		curl_setopt_array($ch, $options);
	}

	$buffer = curl_exec($ch);
	$err = curl_errno($ch);
	curl_close($ch);

	if ($err !== 0) {
		return false;
	} else {
		return $buffer;
	}
}

/**
 * Convert Code page 437 chars to UTF.
 *
 * @param string $str
 *
 * @return string
 */
function cp437toUTF($str)
{
	$out = '';
	for ($i = 0; $i < strlen($str); $i++) {
		$ch = ord($str{$i});
		switch ($ch) {
			case 128: $out .= 'Ç';
				break;
			case 129: $out .= 'ü';
				break;
			case 130: $out .= 'é';
				break;
			case 131: $out .= 'â';
				break;
			case 132: $out .= 'ä';
				break;
			case 133: $out .= 'à';
				break;
			case 134: $out .= 'å';
				break;
			case 135: $out .= 'ç';
				break;
			case 136: $out .= 'ê';
				break;
			case 137: $out .= 'ë';
				break;
			case 138: $out .= 'è';
				break;
			case 139: $out .= 'ï';
				break;
			case 140: $out .= 'î';
				break;
			case 141: $out .= 'ì';
				break;
			case 142: $out .= 'Ä';
				break;
			case 143: $out .= 'Å';
				break;
			case 144: $out .= 'É';
				break;
			case 145: $out .= 'æ';
				break;
			case 146: $out .= 'Æ';
				break;
			case 147: $out .= 'ô';
				break;
			case 148: $out .= 'ö';
				break;
			case 149: $out .= 'ò';
				break;
			case 150: $out .= 'û';
				break;
			case 151: $out .= 'ù';
				break;
			case 152: $out .= 'ÿ';
				break;
			case 153: $out .= 'Ö';
				break;
			case 154: $out .= 'Ü';
				break;
			case 155: $out .= '¢';
				break;
			case 156: $out .= '£';
				break;
			case 157: $out .= '¥';
				break;
			case 158: $out .= '₧';
				break;
			case 159: $out .= 'ƒ';
				break;
			case 160: $out .= 'á';
				break;
			case 161: $out .= 'í';
				break;
			case 162: $out .= 'ó';
				break;
			case 163: $out .= 'ú';
				break;
			case 164: $out .= 'ñ';
				break;
			case 165: $out .= 'Ñ';
				break;
			case 166: $out .= 'ª';
				break;
			case 167: $out .= 'º';
				break;
			case 168: $out .= '¿';
				break;
			case 169: $out .= '⌐';
				break;
			case 170: $out .= '¬';
				break;
			case 171: $out .= '½';
				break;
			case 172: $out .= '¼';
				break;
			case 173: $out .= '¡';
				break;
			case 174: $out .= '«';
				break;
			case 175: $out .= '»';
				break;
			case 176: $out .= '░';
				break;
			case 177: $out .= '▒';
				break;
			case 178: $out .= '▓';
				break;
			case 179: $out .= '│';
				break;
			case 180: $out .= '┤';
				break;
			case 181: $out .= '╡';
				break;
			case 182: $out .= '╢';
				break;
			case 183: $out .= '╖';
				break;
			case 184: $out .= '╕';
				break;
			case 185: $out .= '╣';
				break;
			case 186: $out .= '║';
				break;
			case 187: $out .= '╗';
				break;
			case 188: $out .= '╝';
				break;
			case 189: $out .= '╜';
				break;
			case 190: $out .= '╛';
				break;
			case 191: $out .= '┐';
				break;
			case 192: $out .= '└';
				break;
			case 193: $out .= '┴';
				break;
			case 194: $out .= '┬';
				break;
			case 195: $out .= '├';
				break;
			case 196: $out .= '─';
				break;
			case 197: $out .= '┼';
				break;
			case 198: $out .= '╞';
				break;
			case 199: $out .= '╟';
				break;
			case 200: $out .= '╚';
				break;
			case 201: $out .= '╔';
				break;
			case 202: $out .= '╩';
				break;
			case 203: $out .= '╦';
				break;
			case 204: $out .= '╠';
				break;
			case 205: $out .= '═';
				break;
			case 206: $out .= '╬';
				break;
			case 207: $out .= '╧';
				break;
			case 208: $out .= '╨';
				break;
			case 209: $out .= '╤';
				break;
			case 210: $out .= '╥';
				break;
			case 211: $out .= '╙';
				break;
			case 212: $out .= '╘';
				break;
			case 213: $out .= '╒';
				break;
			case 214: $out .= '╓';
				break;
			case 215: $out .= '╫';
				break;
			case 216: $out .= '╪';
				break;
			case 217: $out .= '┘';
				break;
			case 218: $out .= '┌';
				break;
			case 219: $out .= '█';
				break;
			case 220: $out .= '▄';
				break;
			case 221: $out .= '▌';
				break;
			case 222: $out .= '▐';
				break;
			case 223: $out .= '▀';
				break;
			case 224: $out .= 'α';
				break;
			case 225: $out .= 'ß';
				break;
			case 226: $out .= 'Γ';
				break;
			case 227: $out .= 'π';
				break;
			case 228: $out .= 'Σ';
				break;
			case 229: $out .= 'σ';
				break;
			case 230: $out .= 'µ';
				break;
			case 231: $out .= 'τ';
				break;
			case 232: $out .= 'Φ';
				break;
			case 233: $out .= 'Θ';
				break;
			case 234: $out .= 'Ω';
				break;
			case 235: $out .= 'δ';
				break;
			case 236: $out .= '∞';
				break;
			case 237: $out .= 'φ';
				break;
			case 238: $out .= 'ε';
				break;
			case 239: $out .= '∩';
				break;
			case 240: $out .= '≡';
				break;
			case 241: $out .= '±';
				break;
			case 242: $out .= '≥';
				break;
			case 243: $out .= '≤';
				break;
			case 244: $out .= '⌠';
				break;
			case 245: $out .= '⌡';
				break;
			case 246: $out .= '÷';
				break;
			case 247: $out .= '≈';
				break;
			case 248: $out .= '°';
				break;
			case 249: $out .= '∙';
				break;
			case 250: $out .= '·';
				break;
			case 251: $out .= '√';
				break;
			case 252: $out .= 'ⁿ';
				break;
			case 253: $out .= '²';
				break;
			case 254: $out .= '■';
				break;
			case 255: $out .= ' ';
				break;
			default : $out .= chr($ch);
		}
	}
	return $out;
}

// Function inpsired by c0r3@newznabforums adds country flags on the browse page.
function release_flag($x, $t)
{
	$y = $d = "";

	if (preg_match('/\bCzech\b/i', $x)) {
		$y = "cz";
		$d = "Czech";
	} else if (preg_match('/Chinese|Mandarin|\bc[hn]\b/i', $x)) {
		$y = "cn";
		$d = "Chinese";
	} else if (preg_match('/German(bed)?|\bger\b/i', $x)) {
		$y = "de";
		$d = "German";
	} else if (preg_match('/Danish/i', $x)) {
		$y = "dk";
		$d = "Danish";
	} else if (preg_match('/English|\beng?\b/i', $x)) {
		$y = "en";
		$d = "English";
	} else if (preg_match('/Spanish/i', $x)) {
		$y = "es";
		$d = "Spanish";
	} else if (preg_match('/Finnish/i', $x)) {
		$y = "fi";
		$d = "Finnish";
	} else if (preg_match('/French|Vostfr/i', $x)) {
		$y = "fr";
		$d = "French";
	} else if (preg_match('/\bGreek\b/i', $x)) {
		$y = "gr";
		$d = "Greek";
	} else if (preg_match('/Hungarian|\bhun\b/i', $x)) {
		$y = "hu";
		$d = "Hungarian";
	} else if (preg_match('/Hebrew|Yiddish/i', $x)) {
		$y = "il";
		$d = "Hebrew";
	} else if (preg_match('/\bHindi\b/i', $x)) {
		$y = "in";
		$d = "Hindi";
	} else if (preg_match('/Italian|\bita\b/i', $x)) {
		$y = "it";
		$d = "Italian";
	} else if (preg_match('/Japanese|\bjp\b/i', $x)) {
		$y = "jp";
		$d = "Japanese";
	} else if (preg_match('/Korean|\bkr\b/i', $x)) {
		$y = "kr";
		$d = "Korean";
	} else if (preg_match('/Flemish|\b(Dutch|nl)\b|NlSub/i', $x)) {
		$y = "nl";
		$d = "Dutch";
	} else if (preg_match('/Norwegian/i', $x)) {
		$y = "no";
		$d = "Norwegian";
	} else if (preg_match('/Tagalog|Filipino/i', $x)) {
		$y = "ph";
		$d = "Tagalog|Filipino";
	} else if (preg_match('/Arabic/i', $x)) {
		$y = "pk";
		$d = "Arabic";
	} else if (preg_match('/Polish/i', $x)) {
		$y = "pl";
		$d = "Polish";
	} else if (preg_match('/Portugese/i', $x)) {
		$y = "pt";
		$d = "Portugese";
	} else if (preg_match('/Romanian/i', $x)) {
		$y = "ro";
		$d = "Romanian";
	} else if (preg_match('/Russian/i', $x)) {
		$y = "ru";
		$d = "Russian";
	} else if (preg_match('/Swe(dish|sub)/i', $x)) {
		$y = "se";
		$d = "Swedish";
	} else if (preg_match('/\bThai\b/i', $x)) {
		$y = "th";
		$d = "Thai";
	} else if (preg_match('/Turkish/i', $x)) {
		$y = "tr";
		$d = "Turkish";
	} else if (preg_match('/Cantonese/i', $x)) {
		$y = "tw";
		$d = "Cantonese";
	} else if (preg_match('/Vietnamese/i', $x)) {
		$y = "vn";
		$d = "Vietnamese";
	}
	if ($y !== "" && $t == "browse") {
		$www = WWW_TOP;
		if (!in_array(substr($www, -1), array('\\', '/'))) {
			$www .= DS;
		}

		return '<img title=' . $d . ' src="' . $www . 'themes_shared/images/flags/' . $y . '.png" />';
	} else if ($t == "search") {
		if ($y == "") {
			return false;
		} else {
			return $y;
		}
	}
	return '';
}