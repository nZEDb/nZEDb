<?php
require_once dirname(__FILE__) . '/../../../config.php';

if (!isset($argv[1])) {
	exit((new ColorCLI())->error('This script is not intended to be run manually, it is called from postprocess_threaded.py.'));
}

$options = explode('           =+=            ', $argv[1]);
if (!isset($options[1])) {
	return;
}

switch ($options[1]) {
	case 'additional':
	case 'nfo':
		$pdo = new \nzedb\db\Settings();

		// Create the connection here and pass, this is for post processing, so check for alternate
		$nntp = new NNTP();

		if (($pdo->getSetting('alternate_nntp') == 1 ? $nntp->doConnect(true, true) : $nntp->doConnect()) !== true) {
			exit($c->error('Unable to connect to usenet.'));
		}

		if ($options[1] === 'nfo') {
			(new Nfo(true))->processNfoFiles($nntp, $options[0]);
		} else {
			(new PostProcess(true))->processAdditional($nntp, $options[0]);
		}

		$nntp->doQuit();
		return;

	case 'movie':
		(new PostProcess(true))->processMovies($options[0]);
		echo '.';
		return;

	case 'tv':
		(new PostProcess(true))->processTv($options[0]);
		return;
}