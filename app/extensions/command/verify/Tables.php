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
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 *
 * @author    niel
 * @copyright 2018 nZEDb
 */
namespace app\extensions\command\verify;

use app\models\Groups as Group;
use app\models\Settings;
use app\models\Tables as Schema;
use lithium\data\Connections;
use nzedb\Groups;

class Tables extends \app\extensions\console\Command
{
	public function __construct(array $config = [])
	{
		parent::__construct($config);
	}

	public static function getCBPTables(string $prefix): array
	{
		$tables = [];
		$data = static::find('tpg', ['prefix' => $prefix])->data();
		/* @var $data string[][] */
		foreach ($data as $table) {
			$tables[] = $table['TABLE_NAME'];
		}

		return $tables;
	}

	public function run()
	{
		if (empty($this->request)) {
			return $this->_help();
		}

		foreach ($this->request->params['args'] as $arg) {
			switch ($arg) {
				case 'Settings':
				case 'settings':
					$this->tableSettings();

					break;
				case 'cpb':
					$this->tableSetCPB();

					break;
//				case '':
//					$this->();
//					break;
				default:
					$this->out("Unknown table: '$arg'", ['style' => 'error']);
			}
		}
	}

	protected function _init()
	{
		parent::_init();
	}

	/**
	 * Check the set of tables: collections, binaries, parts, and missed parts for enabled groups
	 * without corresponding table(s).
	 *
	 * @return array All group IDs that had problems, empty if no problems were discovered.
	 */
	protected function tableSetCPB() : array
	{
		$active = Group::find('all',
			[
				'conditions' => [
					'active' => true,
				],
				'fields'	=> [
					'id',
					'name',
				],
				'order'		=> 'name ASC',
			]
		);

		if (\count($active->data()) < 1) {
			$this->out('No active groups found to verify!');
		} else {
			$groups = Group::find('all',
				[
					'fields'     => 'id',
					'conditions' => ['active' => true],
				])->data();
			/* @var $groups string[][] */
			foreach ($groups as $group) {
				$ids[] = $group['id'];
			}

			$binaries = Schema::tpg('binaries');
			$collections = Schema::tpg('collections');
			$parts = Schema::tpg('parts');

			$errors = [];
			//$groups = new Groups();
			/* @var $ids string[][] */
			foreach ($ids as $groupID) {
				if (! \in_array('binaries_' . $groupID, $binaries, false) ||
					! \in_array('collections_' . $groupID, $collections, false) ||
					! \in_array('parts_' . $groupID, $parts, false)
				) {
					echo "Creating missing tables for group id: $groupID" . PHP_EOL;
					$errors[] = $groupID;
					//$groups->createNewTPGTables($groupID);
					Schema::createTPGTablesForId($groupID);
				} else {
					echo "Group id '$groupID' has all its tables." . PHP_EOL;
				}
			}

			return $errors;
		}
	}

	protected function tableSettings()
	{
		$output = function($row, $header, &$firstLine, $result) {
			if ($firstLine === true) {
				$this->out('section, subsection, name', ['style' => 'info']);
				$firstLine = false;
			}

			if ($result === false) {
				$this->out(" {$row['section']}, {$row['subsection']}, {$row['name']}: MISSING!",
					['style' => 'error']);
			}
		};

		$validate = static function($row) {
			$result = Settings::value(
				[
					'section'    => $row['section'],
					'subsection' => $row['subsection'],
					'name'       => $row['name'],
				],
				true);

			return $result !== null;
		};

		$dummy = $this->validate(
			[
				'file' => nZEDb_RES . 'db/schema/data/10-settings.tsv',
				'output' => $output,
				'silent' => false,
				'table' => 'Settings',
				'test' => $validate,
			]
		);

		return $dummy;
	}

	protected function defaultOutput($row, $header, &$error)
	{
		if ($error === false) {
			$this->out($header, ['style' => 'primary']);
			$error = true;
		}
		$this->out($row, ['style' => 'info']);
	}

	/**
	 * @param array $options Settings for the validation. Some more optional than others. Entries
	 *                       include:
	 *                       `file`  - filename of data file to use
	 *                       `fix`   - whether to fix failures (false|closure)
	 *                       `output`- closure to format text output.
	 *                       `silent - disable output (boolean)
	 *                       `test`  - closure to use for validation.
	 *
	 * @return bool true if no errors found, false otherwise
	 */
	private function validate(array $options = [])
	{
		$fix = $output = $test = '';
		\extract($options, EXTR_IF_EXISTS | EXTR_REFS); // create short-name variable refs

		if (! \is_callable($test)) {
			throw new \InvalidArgumentException('The option "test" must be a closure to perform the test!');
		}

		if (! \file_exists($options['file'])) {
			throw new \InvalidArgumentException("Unable to find {$options['file']}!");
		}
		$rows = \file($options['file']);

		if (! \is_array($rows)) {
			throw new \InvalidArgumentException("File {$options['file']} did not return a list of rows!");
		}

		// Move the column names/order off of the array.
		$header = \trim(\array_shift($rows));
		$columns = \explode(',', $header);
		\array_walk($columns,
			function(&$value) {
				$value = \trim($value);
			}
		);


		if ($options['silent'] != true) {
			$this->out("Verifying `{$options['table']}` table...", ['style' => 'primary']);
		}

		$result = $firstLine = true;
		$error = false;
		foreach ($rows as $row) {
			$data = \array_combine($columns, \explode("\t", $row));
			$check = $test($data, $columns);
			if ($options['silent'] === false) {
				if (\is_callable($output)) {
					$output($data, $header, $firstLine, $check);
				} else {
					$this->defaultOutput($row, $header, $error);
				}
			}

			if ($check === false) {
				if (\is_callable($fix)) {
					$fix($data);
				}

				$error = true;
			}
		}

		if ($error === false) {
			$this->out('No problems found.', ['style' => 'primary']);
		} elseif ($options['fix'] === false) {
			$this->out('Please fix the above problems.', ['style' => 'warning']);
		}

		return $result;
	}
}
