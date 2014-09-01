<?php

require_once dirname(__FILE__) . '/../www/config.php';

$output = new \ColorCLI();
echo $output->primary("Checking for files in the renamed directories.");
$status = 0;

$dirs = array(
	[
		'old' => 'misc/testing/DB_scripts',
		'newpath' => 'misc/testing/DB'
	],
	[
		'old' => 'misc/testing/Dev_testing/Subject_testing',
		'newpath' => 'misc/testing/Dev/Subject'
	],
	[
		'old' => 'misc/testing/Dev_testing',
		'newpath' => 'misc/testing/Dev'
	],
	[
		'old' => 'misc/testing/PostProc_testing',
		'newpath' => 'misc/testing/PostProc'
	],
	[
		'old' => 'misc/testing/Regex_testing',
		'newpath' => 'misc/testing/Regex'
	],
	[
		'old' => 'misc/testing/Release_scripts',
		'newpath' => 'misc/testing/Release'
	],
	[
		'old' => 'misc/update_scripts/nix_scripts/tmux/powerline/themes',
		'newpath' => 'misc/update/nix/tmux/powerline/themes'
	],
	[
		'old' => 'misc/update_scripts/nix_scripts/tmux/powerline',
		'newpath' => 'misc/update/nix/tmux/powerline'
	],
	[
		'old' => 'misc/update_scripts/nix_scripts/screen/sequential',
		'newpath' => 'misc/update/nix/screen/sequential'
	],
	[
		'old' => 'misc/update_scripts/nix_scripts/tmux/',
		'newpath' => 'misc/update/nix/tmux'
	],
	[
		'old' => 'misc/update_scripts/nix_scripts',
		'newpath' => 'misc/update/nix'
	],
	[
		'old' => 'misc/update_scripts/python_scripts/lib',
		'newpath' => 'misc/update/python/lib'
	],
	[
		'old' => 'misc/update_scripts/python_scripts',
		'newpath' => 'misc/update/python'
	],
	[
		'old' => 'misc/update_scripts/win_scripts',
		'newpath' => 'misc/update/win'
	],
	[
		'old' => 'misc/update_scripts',
		'newpath' => 'misc/update'
	],
);

$tatus = 0;
foreach ($dirs as $dir) {
	if (isset($dir['old'])) {
		$pathOld = nZEDb_ROOT . $dir['old'];
		if (file_exists($pathOld)) {
			$pathNew = nZEDb_ROOT . $dir['newpath'];
			echo $output->info("Moving contents of '$pathOld' to '$pathNew'");
			$dirIt = new \DirectoryIterator($pathOld);
			foreach ($dirIt as $item) {
				if ($item->isDot()) {
					continue;
				}
				echo $output->info("  Moving {$item->getPathname()}");
				if (rename($item->getPathname(), $pathNew . DIRECTORY_SEPARATOR . $item->getFilename()) === false) {
					echo $output->error("   FAILED!");
					$status = 1;
				}
			}
			$d = dir($pathOld);
			if ($d->read() === false) {
				@unlink($pathOld);
			} else {
				echo $output->error("Could not move all files. Check your permissions!");
			}
		}
	}
}

exit((int) $status);
