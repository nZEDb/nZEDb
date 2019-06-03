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
 * @copyright 2019 nZEDb
 */
require_once __DIR__ . '/../app/config/bootstrap.php';

$baseDir = shell_exec('php ' .__DIR__ . '/../nZEDbBase.php');

if (!empty(DB_SOCKET)) {
	$dsn = DB_SYSTEM . ':unix_socket=' . DB_SOCKET . ';dbname=' . DB_NAME;
} else {
	$dsn = DB_SYSTEM . ':host=' . DB_HOST . ';dbname=' . DB_NAME;
}

$dbc = new PDO($dsn, DB_USER, DB_PASSWORD);

//var_dump($dbc);

return [
	'paths'        => [
		'migrations' => $baseDir . '/resources/db/migrations'
	],
	'environments' => [
		'default_migration_table' => 'phinxlog',
		'default_database'        => 'dev',
		'dev'                     => [
			'name'	=> DB_NAME,
			'connection' => $dbc,
		]
	]
];
?>
