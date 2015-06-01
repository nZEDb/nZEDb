<?php

use nzedb\libraries\Forking;

declare(ticks = 1);

require('.do_not_run/require.php');

(new Forking())->processWorkType('releases');
