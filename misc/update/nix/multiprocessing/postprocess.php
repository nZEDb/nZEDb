<?php
declare(ticks=1);
require(dirname(__FILE__) . '/../../config.php');
(new \nzedb\libraries\Forking())->processWorkType('postprocess');