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

$start = ['start', 'run', 'resume', 'continue'];
$stop  = ['stop', 'end', 'finish', 'halt'];

$options = implode(' | ', $start) . ' | ' . implode(' | ', $stop);

$message = <<<HELP_TEXT
  Usage: {$argv[0]} [$options]
Start or stop the processing of tmux scripts. This is functionally equivalent to (un)setting the
'tmux running' setting in admin.
HELP_TEXT;

if ($argc == 1) {
	exit($message);
}

if (in_array($argv[1], $start)) {
	passthru('php start.php');
} else if (in_array($argv[1], $stop)) {
	passthru('php stop.php');
} else {
	echo "Unrecognised command '{$argv[1]}''\n";
	exit($message);
}

?>
