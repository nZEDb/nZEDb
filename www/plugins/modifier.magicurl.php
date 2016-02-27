<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
/**
 * Smarty magicurl modifier plugin
 *
 * Type:     modifier<br>
 * Name:     magicurl<br>
 * Purpose:  turn www addresses into hyperlinks
 * @author   bb
 * @param string
 * @return string
 */
function smarty_modifier_magicurl($str, $dereferrer="") {
	return preg_replace('/(https?):\/\/([A-Za-z0-9\._\-\/\?=&;%]+)/is', '<a href="'.$dereferrer.'$1://$2" target="_blank">$1://$2</a>', $str);
}
?>