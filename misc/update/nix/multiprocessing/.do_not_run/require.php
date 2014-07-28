<?php
require_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'config.php';

if (!is_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'settings.php')) {
	exit("Cannot run multiprocessing, no settings file found!");
}
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'settings.php';
