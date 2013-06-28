<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/releases.php");

if (isset($argv[1]) && is_numeric($argv[1]))
{
	$releases = new Releases(true);
	if ($argv[1] == 1)
		$releases->processReleasesStage1('', true);
	elseif ($argv[1] == 12)
	{
		$releases->processReleasesStage1('', true);
		$releases->processReleasesStage2('', true);
	}
	elseif ($argv[1] == 123)
	{
		$releases->processReleasesStage1('', true);
		$releases->processReleasesStage2('', true);
		$releases->processReleasesStage3('', true);
	}
	elseif ($argv[1] == 456)
	{
		$releases->processReleasesStage2('', true);
		$releases->processReleasesStage3('', true);
		$releases->processReleasesStage4567_loop(1, 0, '', true);
		$releases->processReleasesStage7b('', true);
	}

}

