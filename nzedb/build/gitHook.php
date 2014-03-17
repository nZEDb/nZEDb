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
require_once realpath(dirname(__DIR__) . '/../www/config.php');

define('nZEDb_GIT', nZEDb_ROOT . '.git' . DS);
define('nZEDb_HOOKS', nZEDb_GIT . 'hooks' . DS);
define('PRE_COMMIT_HOOK', 'pre-commit');

if ($argc > 1 && $argv[1]) {
	 define('VERBOSE', true);
} else {
	 define('VERBOSE', false);
}

$changed = false;
$source = __DIR__ . DS . 'git-hooks' . DS . PRE_COMMIT_HOOK;
$target = nZEDb_HOOKS . DS . PRE_COMMIT_HOOK;

if (!file_exists(nZEDb_HOOKS . PRE_COMMIT_HOOK)) {
	copy($source, $target);
}
chmod($target, 0774);

$out = new ColorCLI();

$file = file($target);
$count = count($file);
$index = 0;
while ($index < $count) {
	$file[$index] = trim($file[$index]);
	if (preg_match('/^#nZEDb hook\s*-\s*(.+)$/', $file[$index], $match)) {
		if (VERBOSE) {
			echo $out->primary("Matched: " . $file[$index]);
		}
		$index++;
		$file[$index] = trim($file[$index]);
		switch ($match[1]) {
			case 'update version info':
			case 'run hooks':
				$hook = '/usr/bin/php ' . nZEDb_LIB . 'build/git-hooks/runHooks.php';
				if ($hook != $file[$index]) {
					if (VERBOSE) {
						echo $out->primary('Replace: "' . $file[$index] . '" with "' . $hook . '"');
					}
					$file[$index] = $hook;
					$changed = true;
				} else {
					echo $out->primary("Skipped: " . $file[$index]);
				}
				break;

			default:
				$index--;
				echo $out->error('Invalid hook placeholder!!');
				break;
		}
	} else {
		if (VERBOSE) {
			echo $out->primary("Skipped: " . $file[$index]);
		}
	}
	$index++;
}

if ($changed === false) {
	echo $out->warning('Unable to find any hooks needing updates!');
} else {
	 if (file_put_contents($target, implode("\n", $file)) === false) {
		 echo $out->error("Error writing file to disc!!");
	 }
}

?>
