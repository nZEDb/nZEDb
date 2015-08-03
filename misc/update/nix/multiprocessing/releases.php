<?php

require('.do_not_run/require.php');

use nzedb\libraries\Forking;

declare(ticks = 1);

(new Forking())->processWorkType('releases');
