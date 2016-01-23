<?php
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\NameFixer;
use nzedb\NNTP;

$nntp = new NNTP();

$nameFixer = new NameFixer();

$nameFixer->fixNamesWithSRR(2, 1, 1, 1, 1, $nntp);
