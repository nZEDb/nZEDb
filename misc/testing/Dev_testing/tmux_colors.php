<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/tmux.php");

$tmux = new Tmux();
$colors_start = $tmux->get()->COLORS_START;
$colors_end = $tmux->get()->COLORS_END;
$colors_exc = $tmux->get()->COLORS_EXC;

function get_color($colors_start, $colors_end, $colors_exc)
{
	$exceptions = str_replace(".", ".", $colors_exc);
	$exceptions = explode( ",", $exceptions );
	sort($exceptions);
	$number = mt_rand($colors_start, $colors_end - count($exceptions));
	foreach ($exceptions as $exception)
	{
		if ($number >= $exception)
			$number++;
		else
			break;
	}
	return $number;
}

$passed = $failed = $pass = $fail = "";
$cpass = 0;
$cfail = 0;

foreach (range(0, 256) as $number) {
	$i = 0;
	do
	{
		$color = get_color($colors_start, $colors_end, $colors_exc);
		if ($color == $number)
		{
			$passed = "\033[38;5;${color}mThis Color is \033[0m".str_pad($number,3,'0',STR_PAD_LEFT);
			$cpass++;
			if ($cpass % 8 == 0)
				$pass .= $passed."    "."\n";
			else
				$pass .= $passed."\t";
		}
		if ($i == 10000)
		{
			$failed = "Color \033[38;5;${color}m".str_pad($number,3,'0',STR_PAD_LEFT)."\033[0m is excluded";
			$cfail++;
			if ($cfail % 8 == 0)
				$fail .= $failed."\n";
			else
				$fail .= $failed."\t";
		}
		$i++;
	} while ($color != $number && $i <= 10000);
}

passthru("clear");
echo "\n\033[38;5;35mThese are all of the colors available to the tmux scripts as set in tmux-edit.\nIf you see the colors repeat while the numbers change, you only have 16 colors available, try enabling 'xterm-256color' in your terminal.\n\n";
echo $pass."\n\n";
echo $fail."\n";
