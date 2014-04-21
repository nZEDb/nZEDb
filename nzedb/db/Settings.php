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
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2014 nZEDb
 */
namespace nzedb\db;

require_once
	dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'config.php';

use nzedb\utility\Utility;

class Settings extends DB
{
	private $table = 'settings';

	public function __construct (array $options = array())
	{
		parent::__construct($options);
		$table  = $this->table;
		$result = self::queryExec("describe $table");
		if ($result === false || empty($result)) {
			$this->table = 'site';
		}
		return self::$pdo;
	}

	/**
	 * Retrieve one or all settings from the Db as a string or an array;
	 *
	 * @param array|string $options Name of setting to retrieve (null for all settings)
	 *                              or array of 'feature', 'section', 'name' of setting{s} to retrieve
	 *
	 * @return string|array|bool
	 */
	public function getSetting ($options = array())
	{
		if (!is_array($options)) {
			$options = ['name' => $options];
		}
		$defaults = array(
			'section'    => '',
			'subsection' => '',
			'name'       => null,
		);
		$options += $defaults;

		if ($this->table == 'settings') {
			$results = $this->_getFromSettings($options);
		} else {
			$results = $this->_getFromSites($options);
		}
		return (count($results) === 1 ? $results[0]['value'] : $results);
	}

	protected function _getFromSettings ($options)
	{
		$results = array();
		$sql     = 'SELECT section, subsection, name, value FROM settings ';
		$where   = $options['section'] .
				   $options['subsection'] . $options['name']; // Can't use expression in empty() < PHP 5.5
		if (!empty($where)) {
			$sql .= "WHERE section = '{$options['section']}' AND subsection = '{$options['subsection']}'";
			$sql .= empty($options['name']) ? '' : " AND name = '{$options['name']}'";
		} else {
			$sql .= "WHERE section = '' AND subsection = ''";
		}
		$sql .= ' ORDER BY section, subsection, name';

		$result = $this->queryArray($sql);
		if ($result !== false) {
			if (!empty($where)) {
				foreach ($result as $row) {
					$results[]['value'] = $row['value'];
				}
			} else {
				foreach ($result as $row) {
					$results[$row['section']][$row['subsection']][$row['name']] = $row['value'];
				}
			}
		}

		return $results;
	}

	protected function _getFromSites ($options)
	{
		$results = array();
		$sql     = 'SELECT setting, value FROM site ';
		if (!empty($options['name'])) {
			$sql .= "WHERE setting = '{$options['name']}'";
		}
		$sql .= ' ORDER BY setting';

		$result = $this->queryArray($sql);
		if ($result !== false) {
			foreach ($result as $row) {
				$results[]['value'] = $row['value'];
			}
		}

		return $results;
	}
}

/*
 * Putting procedural stuff inside class scripts like this is BAD. Do not use this as an excuse to do more.
 * This is a temporary measure until a proper frontend for cli stuff can be implemented with li3.
 */
if (Utility::isCLI()) {
	if (isset($argc) && $argc > 1) {
		$settings = new Settings();
		echo $settings->getSetting($argv[1]);
	}
}

?>
