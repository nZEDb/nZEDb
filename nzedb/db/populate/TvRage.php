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

class TvRage extends PopulateTitles
{
	const SOURCE_DATA = 'http://services.tvrage.com/feeds/show_list.php';

	protected $showList;

	public function  __constructor($options)
	{
		$defaults = [
			'pdo'	=> null,
		];
		$options += $defaults;

		// Prevent certain parameters from being overridden.
		$options['data-source-url']	= self::SOURCE_DATA;
		$options['main-table']		= 'tvrage_titles';

		if (!($options['pdo'] instanceof Settings)) {
			$options['pdo'] = null;
		}

		parent::__construct($options);

		$this->showList = nZEDb_RES . 'tmp' . DS . 'tvrage_shows.xml';
	}

	public function updateTitleList()
	{
		$result = $this->saveShowList();
		if ($result !== false) {
			$result = $this->createTempTable();
			if ($result !== false) {
				$result = $this->loadXMLFromFile($this->showList);
				if ($result !== false) {
					$this->prepareStatements();
					$updated = 0;
					foreach ($result->show as $tvrage) {
						if ($this->checkForDuplicate((string)$tvrage->id) !== false) {
							if (!empty((string)$tvrage->id) && !empty((string)$tvrage->name)) {
								if ($this->insertEntry(
									[
										'country'	=> (string)$tvrage->country,
										'rageid'	=> (string)$tvrage->id,
										'title'		=> (string)$tvrage->name,
									]
									)) {
									$updated++;
								}
							}
						}
					}
					$result = $updated;
				}
			}
		}
		return $result;
	}


	protected function checkForDuplicate($id)
	{
		$result = parent::checkForDuplicate([':id' => $id]);
		if ($result !== false) {
			return ($result['id'] != 0);
		}
		return $result;
	}

	protected function insertEntry(array $parameters)
	{
		return parent::insertEntry([
									':id'		=> $parameters['rageid'],
									':rageid'	=> $parameters['rageid'],
									':title'	=> $parameters['title'],
									':country'	=> $parameters['country'],
									]);
	}

	protected function prepareStatements()
	{
		try {
			$this->checkForDuplicate	= $this->pdo()->prepare('SELECT COUNT(id) FROM tvrage_titles WHERE id = :id');
			$this->insertEntry			= $this->pdo()->prepare(
														sprintf('INSERT INTO %s (id, rageid, releasetitle, country, createddate) VALUES (:id, :rageid, :title, :country, NOW())', $this->tempTable));
		} catch (\PDOException $exp) {
			$this->checkForDuplicate = false;
		} finally {
			if ($this->checkForDuplicate === false) {
				throw new \RuntimeException("Unable to prepare the duplication check statement for TVRage updating!");
			}
		}
	}

	protected function saveShowList()
	{
		return parent::saveSourceFile($this->showList);
	}
}
