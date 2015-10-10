<?php
declare(ticks = 1);

require('.do_not_run/require.php');

use nzedb\libraries\Forking;

// This is the same as the python update_threaded.php
(new Forking())->processWorkType('update_per_group');
