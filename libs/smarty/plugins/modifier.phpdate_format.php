<?php
/**
 * Smarty plugin
 * phpdate_format plugin
 * Sam Easterby-Smith
 * Does exactly what the normal date_format plugin does - only it uses date() rather than strftime()
 * It also supports the various php date constants for doing things like rfc822 dates
 */
/**
 * Include the {@link shared.make_timestamp.php} plugin
 */
// Fix by nZEDb
if (!isset($smarty)) {
	$smarty = new Smarty();
}
switch (true) {
	case is_string($smarty->plugins_dir) && is_dir($smarty->plugins_dir):
		$plugins_dir = $smarty->plugins_dir;
		break;
	case is_array($smarty->plugins_dir):
		$plugins_dir = '';
		foreach ($smarty->plugins_dir as $dir) {
			if (is_string($dir) && is_dir($dir)) {
				$plugins_dir = $dir;
				break;
			}
		}
		break;
	default:
		$plugins_dir = '';
}
if (!is_dir($plugins_dir)) {
	exit('Fatal: Unable to find smarty plugins directory.' . PHP_EOL);
}
// End fix by nZEDb.
require_once ($plugins_dir . 'shared.make_timestamp.php');
/**
 * Smarty phpdate_format modifier plugin
 *
 * Type:     modifier<br>
 * Name:     date_format<br>
 * Purpose:  format datestamps via date()<br>
 * Input:<br>
 *         - string: input date string
 *         - format: strftime format for output
 *         - default_date: default date if $string is empty
 * @link http://smarty.php.net/manual/en/language.modifier.date.format.php
 *          date_format (Smarty online manual)
 * @param string
 * @param string
 * @param string
 * @return string|void
 * @uses smarty_make_timestamp()
 */
function smarty_modifier_phpdate_format($string, $format="Y/m/d H:i:s", $default_date=null)
{
  /*  if (substr(PHP_OS,0,3) == 'WIN') {
		   $_win_from = array ('%e',  '%T',       '%D');
		   $_win_to   = array ('%#d', '%H:%M:%S', '%m/%d/%y');
		   $format = str_replace($_win_from, $_win_to, $format);
	}*/
	if (substr($format,0,5)=='DATE_'){
		switch ($format){
			case 'DATE_ATOM': $nformat=DATE_ATOM; break;
			case 'DATE_COOKIE': $nformat=DATE_COOKIE; break;
			case 'DATE_ISO8601': $nformat=DATE_ISO8601; break;
			case 'DATE_RFC822': $nformat="D, d M y H:i:s O"; break; //The php constant is not quite right - as the time-zone comes out with invalid values like "UTC"...
			case 'DATE_RFC850': $nformat=DATE_RFC850; break;
			case 'DATE_RFC1036': $nformat=DATE_RFC1036; break;
			case 'DATE_RFC1123': $nformat=DATE_RFC1123; break;
			case 'DATE_RFC2822': $nformat=DATE_RFC2822; break;
			case 'DATE_RFC3339': $nformat=DATE_RFC3339; break;
			case 'DATE_RSS': $nformat="D, d M Y H:i:s O"; break; //as rfc822 ...
			case 'DATE_W3C': $nformat=DATE_W3C; break;
		}
	} else {
		$nformat=$format;
	}
	if($string != '') {
		return date($nformat, smarty_make_timestamp($string));
	} elseif (isset($default_date) && $default_date != '') {
		return date($nformat, smarty_make_timestamp($default_date));
	} else {
		return;
	}
}
/* vim: set expandtab: */
?>