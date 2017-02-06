<?php

require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nzedb\Steam;

$steam = new Steam();

$steam->populateSteamAppsTable();
