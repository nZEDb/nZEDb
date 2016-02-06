<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
/**
 * Smarty date modifier plugin
 * Purpose:  converts unix timestamps or datetime strings to words
 * Type:     modifier<br>
 * Name:     daysAgo<br>
 * @author   Stephan Otto
 * @param string
 * @return string
 */
function smarty_modifier_daysAgo($date)
{
	if ($date == "")
		return "n/a";
	$sec = mktime(0,0,0,date("m"), date("d"), date("Y")) - (( strtotime($date)) ? strtotime(date("Y-m-d", strtotime($date))) : strtotime(date("Y-m-d", $date)));
	$min = $sec / 60;
	$hrs = $min / 60;
	$days = $sec/60/60/24;
	if ( $hrs <= 24) return ' Today';
	if ($days >= 365)
	{
		$years = round(($days/365), 1);
		return $years.' Yr'.($years!=1?"s":"").' ago';
	}
	else if ($days >= 90)
	{
		return round($days/7).' Wks ago';
	}
	else if ($days <= 2)
		return 'Yesterday';
	else
	{
		return round($days, 0).'d ago';
	}
}
?>