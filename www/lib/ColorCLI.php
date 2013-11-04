<?php
// Original taken from https://gist.github.com/donatj/1315354 by Jesse Donat.
// Modified by ThePeePs.

class ColorCLI {

	static $foreground_colors = array(
		'black'        => '30',
		'blue'         => '34',
		'green'        => '32',
		'cyan'         => '36',
		'red'          => '31',
		'purple'       => '35',
		'yellow'       => '33',
		'gray'         => '37',
	);

	// Feel free to add any other colors that you like here.
	static $colors256 = array(
		'green'        => '119',    'orange'     => '172',
		'yellow'       => '011',    'pink'       => '201',
		'purple'       => '092',    'turquoise'  => '039',
		'blue'         => '027',    'red'        => '001'
	);

	static $background_colors = array(
		'black'        => '40',   'red'          => '41',
		'green'        => '42',   'yellow'       => '43',
		'blue'         => '44',   'magenta'      => '45',
		'cyan'         => '46',   'gray'         => '47',
	);

	static $options = array(
		'norm'         => '0',    'bold'         => '1',
		'dim'          => '2',    'uline'        => '4',
		'blink'        => '5',    'rev'          => '7',
		'hidden'       => '8',    'crossout'     => '9',
	);


	public static function bell ($count = 1)
	{
		echo str_repeat("\007", $count);
	}

	public static function setColor ($opt, $fg = "none", $bg = "none")
	{
		$colored_string = "\033[".self::$options[$opt];
		if (isset(self::$foreground_colors[$fg]))
			$colored_string .= ";".self::$foreground_colors[$fg];
		if (isset(self::$background_colors[$bg]))
			$colored_string .= ";".self::$background_colors[$bg];
		$colored_string .= "m";
		return $colored_string;
	}

	// If you would like to use one of the 256 colors as the bg, choose opt 7 (reverse)
	public static function set256 ($fg, $opt = "none", $bg = "none")
	{
		$colored_string = "\033[38;5;".self::$colors256[$fg];
		if (isset(self::$options[$opt]) && $opt != 'norm')
			$colored_string .= ";".self::$options[$opt];
		if (isset(self::$background_colors[$bg]))
			$colored_string .= ";".self::$background_colors[$bg];
		$colored_string .= "m";
		return $colored_string;
	}

	public static function rsetColor ()
	{
		return "\033[0m";
	}

}
