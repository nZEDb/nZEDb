<?php
declare(ticks = 1);

require('.do_not_run/require.php');

(new \nzedb\libraries\Forking())->processWorkType('releases');
