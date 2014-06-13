<?php
// File is here for testing, will be deleted once merged to dev.

require_once('www/config.php');

(new ProcessAdditional(true, new NNTP(true), new nzedb\db\DB(), (new Sites())->get()))->start();