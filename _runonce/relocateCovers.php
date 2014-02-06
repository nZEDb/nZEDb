<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link <http://www.gnu.org/licenses/>.
 * @author niel
 * @copyright 2014 nZEDb
 */
require_once realpath(dirname(__FILE__) . '/../www/config.php');

define('WIN', strtolower(PHP_OS) == 'windows');

$source = nZEDb_WWW . 'covers';
$target = nZEDb_ROOT . 'resources';

if (! WIN && file_exists($source)) {
	setPerms($target);
	setPerms($source);

	$contents = scandir($source);
	if (count($contents) > 2) {
		passthru('mv ' . $source . '/* ' . $target . '/covers', $status);
		if ($status) {
			die("Damn, something went wrong!\n");
		}
	}
}

$dirs = $files = array();
$rItIt = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator(
		$source,
		FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::UNIX_PATHS
		)
	);

while ($rItIt->valid()) {
	if (!$rItIt->isDot()) {
		$files[] = $rItIt->key();
	} else {
		if (preg_match('#^(.+)[^.]\.{1,2}$#', $rItIt->key(), $match)) {
			$dirs[] = $match[1];
		}
	}
	$rItIt->next();
}
$dirs = array_unique($dirs);
rsort($dirs); // Sort in reverse order so deleting will remove the deepest first
sort($files);

// On *nix the earlier 'mv' should have moved most if not all files, so this will
// be quick or skipped entirely. For windows users this is where the 'moving' is
// done and can be very long in processing.
if (count($files)) {
	$rItIt->rewind();
	while ($rItIt->valid()) {
		if (!$rItIt->isDot()) {
			echo "Copying to: $target/covers/{$rItIt->getSubPathName()} ";
			if (copy($rItIt->key(), $target . '/covers/' . $rItIt->getSubPathName())) {
				echo "Done\n";
				@unlink($rItIt->key());
			} else {
				echo "Failed!\n";
			}
		}
		$rItIt->next();
	}
} else {
	echo "Excellent, files were already moved\n";
}

echo "Checking directories are empty before deleting them.\n";
foreach ($dirs as $dir) {
	echo "Checking '$dir' ";
	$contents = scandir($dir);
	if (count($contents) == 2) {
		echo "is empty - ";
		if (rmdir($dir)) {
			echo "deleted!\n";
		} else {
			echo "failed!\n";
		}
	} else {
		echo "has " . (count($contents) - 2) . " files in it\n";
	}
}


function setPerms($path)
{
	exec('chmod -R 777 ' . $path);
}

?>
