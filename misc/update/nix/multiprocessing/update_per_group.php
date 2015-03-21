<?php
declare(ticks = 1);

require('.do_not_run/require.php');

// This is the same as the python update_threaded.php
(new \nzedb\libraries\Forking())->processWorkType('update_per_group');
