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
namespace nzedb\db\populate;

use nzedb\db\Settings;
use nzedb\utility\Misc;

class PopulateTitles
{
	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var \SimpleXMLElement
	 */
	protected $dataXML;

	/**
	 * @var \PDOStatement
	 */
	protected $checkForDuplicate;

	/**
	 * @var \PDOStatement
	 */
	protected $insertEntry;

	protected $mainTable;

	/**
	 * URL of source data.
	 *
	 * @var string
	 */
	protected $sourceURL;

	protected $tempTable;

	public function  __constructor($options)
	{
		$defaults = [
			'pdo'	=> null,
		];
		$options += $defaults;

		$this->sourceURL = $options['data-source-url'];
		$this->mainTable = $options['main-table'];
		$this->tempTable = $options['main-table'] . '_tmp';

		if (isset($options['pdo']) && $options['pdo'] instanceof Settings) {
			$this->pdo = $options['pdo'];
		}
	}

	protected function checkForDuplicate(array $parameters)
	{
		if ($this->checkForDuplicate instanceof \PDOStatement) {
			$this->checkForDuplicate->execute($parameters);
			return $this->checkForDuplicate->fetchAll();
		} else {
			throw new \RuntimeException("Duplicate check query not yet prepared!");
		}
	}

	protected function createTempTable()
	{
		// Clear the old temporary table.
		$sql = "DROP TABLE IF EXISTS {$this->tempTable}";
		$result = $this->pdo()->exec($sql);

		if ($result !== false) {
			$sql = "CREATE TABLE {$this->tempTable} LIKE {$this->mainTable}";
			$result = $this->pdo()->exec($sql);
		}
		return $result;
	}

	protected function insertEntry(array $parameters)
	{
		if ($this->insertEntry instanceof \PDOStatement) {
			$this->insertEntry->execute($parameters);
			return $this->insertEntry->fetchAll();
		} else {
			throw new \RuntimeException("Insertion query not yet prepared!");
		}
	}

	protected function loadXMLFromFile($file)
	{
		$useErrors = libxml_use_internal_errors(true);
		$this->dataXML = simplexml_load_file($file);

		if (!$this->dataXML) {
			foreach (libxml_get_errors() as $error) {
				echo "\t", $error->message;
			}
		}

		libxml_use_internal_errors($useErrors);
		return ($this->dataXML !== false);
	}

	protected function pdo()
	{
		if ($this->pdo === null) {
			$this->pdo = new Settings();
		}
		return $this->pdo;
	}

	/**
	 * @param string $pathname full path to file for saving. Will be overwritten.
	 *
	 * @return bool|int
	 */
	protected function saveSourceFile($pathname)
	{
		$result = false;
		$file = Misc::getUrl(['url' => $this->sourceURL]);
		if ($file !== false) {
			$result = file_put_contents($pathname, $file);
		}
		return $result;
	}
}
