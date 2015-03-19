<?php
// Original taken from https://gist.github.com/donatj/1315354 by Jesse Donat.
// Modified by ThePeePs.

class ColorCLI
{
	public static $foreground_colors = [
		'Black' => '30',
		'Blue' => '34',
		'Green' => '32',
		'Cyan' => '36',
		'Red' => '31',
		'Purple' => '35',
		'Yellow' => '33',
		'Gray' => '37',
	];
	// Feel free to add any other colors that you like here.
	public static $colors256 = [
		'Gray' => '008', 'Red' => '009',
		'Green' => '010', 'Yellow' => '011',
		'Blue' => '012', 'Purple' => '013',
		'Cyan' => '014', 'White' => '015',
		'Grey1' => '016', 'NavyBlue' => '017',
		'DarkBlue' => '018', 'Blue2' => '019',
		'Blue3' => '020', 'Blue1' => '021',
		'DarkGreen' => '022', 'DeepSkyBlue5' => '023',
		'DeepSkyBlue6' => '024', 'DeepSkyBlue7' => '025',
		'DodgerBlue2' => '026', 'DodgerBlue1' => '027',
		'Green4' => '028', 'SpringGreen5' => '029',
		'Turquoise1' => '030', 'DeepSkyBlue2' => '031',
		'DeepSkyBlue3' => '032', 'DodgerBlue' => '033',
		'Green2' => '034', 'SpringGreen3' => '035',
		'DarkCyan' => '036', 'LightSeaGreen' => '037',
		'DeepSkyBlue1' => '038', 'DeepSkyBlue' => '039',
		'Green3' => '040', 'SpringGreen4' => '041',
		'SpringGreen1' => '042', 'Cyan3' => '043',
		'DarkTurquoise' => '044', 'Turquoise' => '045',
		'Green1' => '046', 'SpringGreen2' => '047',
		'SpringGreen' => '048', 'MediumSpringGreen' => '049',
		'Cyan2' => '050', 'Cyan1' => '051',
		'DarkRed' => '052', 'DeepPink5' => '053',
		'Purple4' => '054', 'Purple5' => '055',
		'Purple3' => '056', 'BlueViolet' => '057',
		'Orange2' => '058', 'Grey2' => '059',
		'MediumPurple6' => '060', 'SlateBlue1' => '061',
		'SlateBlue2' => '062', 'RoyalBlue1' => '063',
		'Chartreuse5' => '064', 'DarkSeaGreen7' => '065',
		'PaleTurquoise1' => '066', 'SteelBlue' => '067',
		'SteelBlue3' => '068', 'CornflowerBlue' => '069',
		'Chartreuse3' => '070', 'DarkSeaGreen8' => '071',
		'CadetBlue' => '072', 'CadetBlue1' => '073',
		'SkyBlue2' => '074', 'SteelBlue1' => '075',
		'Chartreuse4' => '076', 'PaleGreen2' => '077',
		'SeaGreen3' => '078', 'Aquamarine2' => '079',
		'MediumTurquoise' => '080', 'SteelBlue2' => '081',
		'Chartreuse1' => '082', 'SeaGreen2' => '083',
		'SeaGreen' => '084', 'SeaGreen1' => '085',
		'Aquamarine' => '086', 'DarkSlateGray1' => '087',
		'DarkRed1' => '088', 'DeepPink6' => '089',
		'DarkMagenta' => '090', 'DarkMagenta1' => '091',
		'DarkViolet' => '092', 'Purple1' => '093',
		'Orange3' => '094', 'LightPink2' => '095',
		'Plum3' => '096', 'MediumPurple4' => '097',
		'MediumPurple5' => '098', 'SlateBlue' => '099',
		'Yellow5' => '100', 'Wheat1' => '101',
		'Grey3' => '102', 'LightSlateGrey' => '103',
		'MediumPurple' => '104', 'LightSlateBlue' => '105',
		'Yellow6' => '106', 'DarkOliveGreen3' => '107',
		'DarkSeaGreen' => '108', 'LightSkyBlue1' => '109',
		'LightSkyBlue2' => '110', 'SkyBlue1' => '111',
		'Chartreuse2' => '112', 'DarkOliveGreen4' => '113',
		'PaleGreen3' => '114', 'DarkSeaGreen5' => '115',
		'DarkSlateGray2' => '116', 'SkyBlue' => '117',
		'Chartreuse' => '118', 'LightGreen' => '119',
		'LightGreen1' => '120', 'PaleGreen' => '121',
		'Aquamarine1' => '122', 'DarkSlateGray' => '123',
		'Red2' => '124', 'DeepPink7' => '125',
		'MediumVioletRed' => '126', 'Magenta4' => '127',
		'DarkViolet1' => '128', 'Purple2' => '129',
		'DarkOrange1' => '130', 'IndianRed' => '131',
		'HotPink3' => '132', 'MediumOrchid3' => '133',
		'MediumOrchid' => '134', 'MediumPurple2' => '135',
		'DarkGoldenrod' => '136', 'LightSalmon1' => '137',
		'RosyBrown' => '138', 'Grey4' => '139',
		'MediumPurple3' => '140', 'MediumPurple1' => '141',
		'Gold1' => '142', 'DarkKhaki' => '143',
		'NavajoWhite1' => '144', 'Grey5' => '145',
		'LightSteelBlue2' => '146', 'LightSteelBlue' => '147',
		'Yellow3' => '148', 'DarkOliveGreen5' => '149',
		'DarkSeaGreen6' => '150', 'DarkSeaGreen3' => '151',
		'LightCyan2' => '152', 'LightSkyBlue' => '153',
		'GreenYellow' => '154', 'DarkOliveGreen2' => '155',
		'PaleGreen1' => '156', 'DarkSeaGreen4' => '157',
		'DarkSeaGreen1' => '158', 'PaleTurquoise' => '159',
		'Red3' => '160', 'DeepPink3' => '161',
		'DeepPink4' => '162', 'Magenta5' => '163',
		'Magenta6' => '164', 'Magenta2' => '165',
		'DarkOrange2' => '166', 'IndianRed1' => '167',
		'HotPink4' => '168', 'HotPink2' => '169',
		'Orchid' => '170', 'MediumOrchid1' => '171',
		'Orange1' => '172', 'LightSalmon2' => '173',
		'LightPink1' => '174', 'Pink1' => '175',
		'Plum2' => '176', 'Violet' => '177',
		'Gold2' => '178', 'LightGoldenrod4' => '179',
		'Tan' => '180', 'MistyRose1' => '181',
		'Thistle1' => '182', 'Plum1' => '183',
		'Yellow4' => '184', 'Khaki1' => '185',
		'LightGoldenrod1' => '186', 'LightYellow' => '187',
		'Grey6' => '188', 'LightSteelBlue1' => '189',
		'Yellow2' => '190', 'DarkOliveGreen' => '191',
		'DarkOliveGreen1' => '192', 'DarkSeaGreen2' => '193',
		'Honeydew' => '194', 'LightCyan1' => '195',
		'Red1' => '196', 'DeepPink2' => '197',
		'DeepPink' => '198', 'DeepPink1' => '199',
		'Magenta3' => '200', 'Magenta1' => '201',
		'OrangeRed' => '202', 'IndianRed2' => '203',
		'IndianRed3' => '204', 'HotPink' => '205',
		'HotPink1' => '206', 'MediumOrchid2' => '207',
		'DarkOrange' => '208', 'Salmon' => '209',
		'LightCoral' => '210', 'PaleVioletRed' => '211',
		'Orchid2' => '212', 'Orchid1' => '213',
		'Orange' => '214', 'SandyBrown' => '215',
		'LightSalmon' => '216', 'LightPink' => '217',
		'Pink' => '218', 'Plum' => '219',
		'Gold' => '220', 'LightGoldenrod2' => '221',
		'LightGoldenrod3' => '222', 'NavajoWhite' => '223',
		'MistyRose' => '224', 'Thistle' => '225',
		'Yellow1' => '226', 'LightGoldenrod' => '227',
		'Khaki' => '228', 'Wheat' => '229',
		'Cornsilk' => '230', 'Grey7' => '231',
		'Grey8' => '232', 'Grey9' => '233',
		'Grey10' => '234', 'Grey11' => '235',
		'Grey12' => '236', 'Grey13' => '237',
		'Grey14' => '238', 'Grey15' => '239',
		'Grey16' => '240', 'Grey17' => '241',
		'Grey18' => '242', 'Grey19' => '243',
		'Grey20' => '244', 'Grey21' => '245',
		'Grey22' => '246', 'Grey23' => '247',
		'Grey24' => '248', 'Grey25' => '249',
		'Grey26' => '250', 'Grey27' => '251',
		'Grey28' => '252', 'Grey29' => '253',
		'Grey30' => '254', 'Grey31' => '255',
	];
	public static $background_colors = [
		'Black' => '40', 'Red' => '41',
		'Green' => '42', 'Yellow' => '43',
		'Blue' => '44', 'Purple' => '45',
		'Cyan' => '46', 'White' => '47',
	];
	public static $options = [
		'Norm' => '0', 'Bold' => '1',
		'Dim' => '2', 'Uline' => '4',
		'Blink' => '5', 'Rev' => '7',
		'Hidden' => '8', 'Crossout' => '9',
	];

	public static function bell($count = 1)
	{
		echo str_repeat("\007", $count);
	}

	public static function setColor($fg, $opt = "None", $bg = "None")
	{
		$colored_string = "\033[" . self::$foreground_colors[$fg];
		if (isset(self::$options[$opt])) {
			$colored_string .= ";" . self::$options[$opt];
		}
		if (isset(self::$background_colors[$bg])) {
			$colored_string .= ";" . self::$background_colors[$bg];
		}
		$colored_string .= "m";
		return $colored_string;
	}

	public static function set256($fg, $opt = "None", $bg = "None")
	{
		$colored_string = "\033[38;5;" . self::$colors256[$fg];
		if (isset(self::$options[$opt]) && $opt != 'Norm') {
			$colored_string .= ";" . self::$options[$opt];
		}
		if (isset(self::$background_colors[$bg])) {
			$colored_string .= ";48;5;" . self::$colors256[$bg];
		}
		$colored_string .= "m";
		return $colored_string;
	}

	public static function debug($str)
	{
		$debugstring = "\033[" . self::$foreground_colors['Gray'] . "mDebug: $str\033[0m\n";
		return $debugstring;
	}

	public static function info($str)
	{
		$infostring = "\033[" . self::$foreground_colors['Purple'] . "mInfo: $str\033[0m\n";
		return $infostring;
	}

	public static function notice($str)
	{
		$noticstring = "\033[38;5;" . self::$colors256['Blue'] . "mNotice: $str\033[0m\n";
		return $noticstring;
	}

	public static function warning($str)
	{
		$warnstring = "\033[" . self::$foreground_colors['Yellow'] . "mWarning: $str\033[0m\n";
		return $warnstring;
	}

	public static function error($str)
	{
		$errorstring = "\033[" . self::$foreground_colors['Red'] . "mError: $str\033[0m\n";
		return $errorstring;
	}

	public static function primary($str)
	{
		$str = "\033[38;5;" . self::$colors256['Green'] . "m$str\033[0m\n";
		return $str;
	}

	public static function header($str)
	{
		$str = "\033[38;5;" . self::$colors256['Yellow'] . "m$str\033[0m\n";
		return $str;
	}

	public static function alternate($str)
	{
		$str = "\033[38;5;" . self::$colors256['DeepPink1'] . "m$str\033[0m\n";
		return $str;
	}

	public static function tmuxOrange($str)
	{
		$str = "\033[38;5;" . self::$colors256['Orange'] . "m$str\033[0m\n";
		return $str;
	}

	public static function primaryOver($str)
	{
		$str = "\033[38;5;" . self::$colors256['Green'] . "m$str\033[0m";
		return $str;
	}

	public static function headerOver($str)
	{
		$str = "\033[38;5;" . self::$colors256['Yellow'] . "m$str\033[0m";
		return $str;
	}

	public static function alternateOver($str)
	{
		$str = "\033[38;5;" . self::$colors256['DeepPink1'] . "m$str\033[0m";
		return $str;
	}

	public static function warningOver($str)
	{
		$str = "\033[38;5;" . self::$colors256['Red'] . "m$str\033[0m";
		return $str;
	}

	public static function rsetColor()
	{
		return "\033[0m";
	}

	/**
	 * Echo message to CLI.
	 *
	 * @param string $message The message.
	 * @param bool $nl Add a new line?
	 * @void
	 */
	public static function doEcho($message, $nl = false)
	{
		echo $message . ($nl ? PHP_EOL : '');
	}
}
