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
require_once nZEDb_LIB . 'utility' . DS .'MoveFileTree.php';

use \nzedb\db\Settings;
use \nzedb\utility;

$dirs = array(
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'testing/DB_scripts',
		'target' => nZEDb_MISC . 'testing/DB'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'testing/Dev_testing/Subject_testing',
		'target' => nZEDb_MISC . 'testing/Dev/Subject'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'testing/Dev_testing',
		'target' => nZEDb_MISC . 'testing/Dev'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'testing/PostProc_testing',
		'target' => nZEDb_MISC . 'testing/PostProc'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'testing/Regex_testing',
		'target' => nZEDb_MISC . 'testing/Regex'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'testing/Release_scripts',
		'target' => nZEDb_MISC . 'testing/Release'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'update_scripts/nix_scripts/tmux/powerline/themes',
		'target' => nZEDb_MISC . 'update/nix/tmux/powerline/themes'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'update_scripts/nix_scripts/tmux/powerline',
		'target' => nZEDb_MISC . 'update/nix/tmux/powerline'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'update_scripts/nix_scripts/screen/sequential',
		'target' => nZEDb_MISC . 'update/nix/screen/sequential'],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'update_scripts/nix_scripts/tmux/',
		'target' => nZEDb_MISC . 'update/nix/tmux'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'update_scripts/nix_scripts',
		'target' => nZEDb_MISC . 'update/nix'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'update_scripts/python_scripts/lib',
		'target' => nZEDb_MISC . 'update/python/lib'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'update_scripts/python_scripts',
		'target' => nZEDb_MISC . 'update/python'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'update_scripts/win_scripts',
		'target' => nZEDb_MISC . 'update/win'	],
	[	'basemv' => false,
		'source' => nZEDb_MISC . 'update_scripts',
		'target' => nZEDb_MISC . 'update'	],

/*
	'covers' =>	[	'source' => nZEDb_WWW . 'covers' . DS,
		'target' =>	nZEDb_RES	],

	// This moves the default nzbpath. If you use another location it will be unaffected
	'nzb' => [	'basemv' => false,
				'source' => nZEDb_ROOT . 'nzbfiles',
				'target' =>	nZEDb_RES . 'nzb'	]
*/
);


foreach ($dirs as $path)
{
	$source = $path['source'];
	$target = $path['target'];
	$basemv = isset($path['basemv']) ? $path['basemv'] : true;

	if (file_exists($source)) {
		$mover = new \nzedb\utility\MoveFileTree($source, $target, $basemv);

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

$pdo = new Settings();
if ($dirs['nzb']['source'] == $pdo->getSetting('nzbpath')) {
	// Update the nzbpath setting if it is the one in use.
	$pdo->queryDirect(sprintf('UPDATE settings SET value = %s WHERE setting = %s LIMIT 1', $dirs['nzb']['target'], 'nzbpath' ));
}

////////////////////////////////////////////////////////////////////////////////

function setPerms($path)
{
	exec('chmod -R 777 ' . $path);
}

?>
