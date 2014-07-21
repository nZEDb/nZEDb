<?php
declare(ticks=1);
require(dirname(__FILE__) . '/../../config.php');
if (is_file(dirname(__FILE__) . '/settings.php')) {
	require_once(dirname(__FILE__) . '/settings.php');
}
(new \nzedb\libraries\Forking())->processWorkType('releases');