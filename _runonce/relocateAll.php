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

//use \Nzedb\Utility;

$dirs = array(
	[	'source' => 'misc/testing/DB_scripts',
		'target' => 'misc/testing/DB'	],
	[	'source' => 'misc/testing/Dev_testing/Subject_testing',
		'target' => 'misc/testing/Dev/Subject'	],
	[	'source' => 'misc/testing/Dev_testing',
		'target' => 'misc/testing/Dev'	],
	[	'source' => 'misc/testing/PostProc_testing',
		'target' => 'misc/testing/PostProc'	],
	[	'source' => 'misc/testing/Regex_testing',
		'target' => 'misc/testing/Regex'	],
	[	'source' => 'misc/testing/Release_scripts',
		'target' => 'misc/testing/Release'	],
	[	'source' => 'misc/update_scripts/nix_scripts/tmux/powerline/themes',
		'target' => 'misc/update/nix/tmux/powerline/themes'	],
	[	'source' => 'misc/update_scripts/nix_scripts/tmux/powerline',
		'target' => 'misc/update/nix/tmux/powerline'	],
	[	'source' => 'misc/update_scripts/nix_scripts/screen/sequential',
		'target' => 'misc/update/nix/screen/sequential'],
	[	'source' => 'misc/update_scripts/nix_scripts/tmux/',
		'target' => 'misc/update/nix/tmux'	],
	[	'source' => 'misc/update_scripts/nix_scripts',
		'target' => 'misc/update/nix'	],
	[	'source' => 'misc/update_scripts/python_scripts/lib',
		'target' => 'misc/update/python/lib'	],
	[	'source' => 'misc/update_scripts/python_scripts',
		'target' => 'misc/update/python'	],
	[	'source' => 'misc/update_scripts/win_scripts',
		'target' => 'misc/update/win'	],
	[	'source' => 'misc/update_scripts',
		'target' => 'misc/update'	],
/*
	[	'source' => nZEDb_WWW . 'covers' . DS,
		'target' =>	nZEDb_ROOT . 'resources' . DS . 'covers' . DS	],

	[	'source' => nZEDb_ROOT . 'nzbfiles',
		'target' =>	nZEDb_RES . 'nzb'	]
 */
	[	'source' => nZEDb_RES . 'tmp' . DS . 'dummy' . DS . 'covers' . DS,
		'target' => nZEDb_RES . 'tmp' . DS	],
);


foreach ($dirs as $path)
{
	$source = $path['source'];
	$target = $path['target'];

	if (file_exists($source)) {
		$mover = new \nzedb\Utility\MoveFileTree($source, $target);

		if (!$mover->isWIndows()) {
			setPerms($target);
			setPerms($source);
		}

		echo "Moving files...\n";
		$mover->move('*');

		echo "Checking directories are empty before deleting them.\n";
		$mover->clearEmpty();
	}
}

////////////////////////////////////////////////////////////////////////////////

function setPerms($path)
{
	exec('chmod -R 777 ' . $path);
}

?>
