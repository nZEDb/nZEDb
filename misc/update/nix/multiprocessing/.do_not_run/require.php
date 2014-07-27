<?php
$path = dirname(__FILE__) . '/../';
require($path . '../../config.php');
if (is_file($path . 'settings.php')) {
	require_once($path . 'settings.php');
}