<?php
require_once '../www/config.php';

$output = new ColorCLI();

$output->primary("Checking for files in the renamed directories.");


$dirs = [
	[
		['old' => '/misc/testing/DB_scripts'],
		['new' => 'DB']
	],
	[
		['old' => '/misc/testing/Dev_testing/Subject_testing'],
		['new' => 'Subject']
	],
	[
		['old' => '/misc/testing/Dev_testing'],
		['new' => 'Dev']
	],
	[
		['old' => '/misc/testing/PostProc_testing'],
		['new' => 'PostProc']
	],
	[
		['old' => '/misc/testing/Regex_testing'],
		['new' => 'Regex']
	],
	[
		['old' => '/misc/testing/Release_scripts'],
		['new' => 'Release']
	],
	[
		['old' => '/misc/update_scripts/nix_scriipt'],
		['new' => 'nix']
	],
	[
		['old' => '/misc/update_scripts/python_scripts'],
		['new' => 'python']
	],
	[
		['old' => '/misc/update_scripts/win_scripts'],
		['new' => 'win']
	],
	[
		['old' => '/misc/update_scripts'],
		['new' => 'update']
	],
];

$tatus = 0;
foreach ($dirs as $dir)
{
	$pathOld = nZEDb_ROOT . $dir['old'];
	if (file_exists($pathOld)) {
		$pathNew = dirname($pathOld) . $dir['new'];
		$output->info("Moving contents of '$pathOld' to '$pathNew'");
		$dirIt = new DirectoryIterator($pathOld);
		foreach ($dirIt as $item) {
			if ($item->isDot()) {
				continue;
			}
			$output->info("  Moving {$item->getPathname()}");
			if (rename($item->getPathname(), $pathNew . $item->getFilename()) === false) {
				$output->error("   FAILED!");
				$status = 1;
			}
		}
		$d = dir($pathOld);
		if ($d->read() === false) {
			@unlink($pathOld);
		} else {
			$output->error("Could not move all files. Check your permissions!");
		}
	}
}

exit((int)$status);
?>