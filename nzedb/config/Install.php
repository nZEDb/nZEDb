<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2018 nZEDb
 */
namespace nzedb\config;


class Install
{
	const LOCK_FILE = self::PATH_CONFIG . 'install.lock';

	const PATH_CONFIG = nZEDb_CONFIGS . DS;

	const PATH_COVERS = nZEDb_RES . 'covers' . DS;

	const PATH_DB_SCHEMA = nZEDb_RES . 'db' . DS . 'schema' . DS;

	const PATH_INSTALL_DIR = nZEDb_WWW . 'install';

	const PATH_NZB = nZEDb_RES . 'nzb' . DS;

	const PATH_SMARTY_TEMPLATES = nZEDb_RES . 'smarty' . DS . 'templates_c' . DS;

	const PATH_TMP = nZEDb_RES . 'tmp' . DS;

	const PATH_UNRAR = self::PATH_TMP . 'unrar' . DS;

	//const WWW_TOP = nZEDb_WWW;


	public function __construct(array $config)
	{
		parent::__construct($config);
	}

	public function isLocked()
	{
		return (file_exists(self::LOCK_FILE) ? true : false);
	}

}

?>
