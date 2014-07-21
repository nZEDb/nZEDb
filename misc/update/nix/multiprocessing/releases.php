<?php
declare(ticks=1);
require(dirname(__FILE__) . '/../../config.php');
if (is_file('settings.php')) {
	require('settings.php');
}
(new \nzedb\libraries\Forking())->processWorkType('releases');