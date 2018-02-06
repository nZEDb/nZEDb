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

namespace app\extensions\command\verify;


use app\models\Settings;


class Tables extends \app\extensions\console\Command
{
	public function __construct(array $config = [])
	{
		parent::__construct($config);
	}

	public function run()
	{
		if (empty($this->request)) {
			return $this->_help();
		}

		foreach ($this->request->params['args'] as $arg) {
			switch ($arg) {
				case 'Settings':
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

	protected function tableSetCPB()
	{
		;
	}

	protected function tableSettings()
	{
		$output = function($row, $header, &$firstLine, $result)
		{
			if ($firstLine === true) {
				$this->out('section, subsection, name', ['style' => 'info']);
				$firstLine = false;
			}

			if ($result === false) {
				$this->out(" {$row['section']}, {$row['subsection']}, {$row['name']}: MISSING!",
					['style' => 'error']);
			}
		};

		$validate = static function($row)
		{
			$result = Settings::value(
				[
					'section'    => $row['section'],
					'subsection' => $row['subsection'],
					'name'       => $row['name']
				],
				true);
			return ($result !== null);
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

	/**
	 * @param array $options	Settings for the validation. Some more optional than others. Entries
	 *                          include:
	 *                          `file`  - filename of data file to use
	 *                          `fix`   - whether to fix failures (false|closure)
	 *                          `output`- closure to format text output.
	 *                          `silent - disable output (boolean)
	 *                          `test`  - closure to use for validation.
	 *
	 * @return boolean	true if no errors found, false otherwise
	 */
	private function validate(array $options = [])
	{
		$fix = $output = $test = '';
		extract($options, EXTR_IF_EXISTS | EXTR_REFS); // create short-name variable refs

		if (!is_callable($test)) {
			throw new \InvalidArgumentException('The option "test" must be a closure to perform the test!');
		}

		if (!file_exists($options['file'])) {
			throw new \InvalidArgumentException("Unable to find {$options['file']}!");
		}
		$rows = file($options['file']);

		if (!is_array($rows)) {
			throw new \InvalidArgumentException("File {$options['file']} did not return a list of rows!");
		}

		// Move the column names/order off of the array.
		$header = trim(array_shift($rows));
		$columns = explode(',', $header);
		array_walk($columns,
			function(&$value)
			{
				$value = trim($value);
			}
		);


		if ($options['silent'] != true) {
			$this->out("Verifying `{$options['table']}` table...", ['style' => 'primary']);
		}

		$result = $firstLine = true;
		$error = false;
		foreach ($rows as $row) {
			$data = array_combine($columns, explode("\t", $row));
			$check = $test($data, $columns);
			if ($options['silent'] === false) {
				if (is_callable($output)) {
					$output($data, $header, $firstLine, $check);
				} else {
					$this->defaultOutput($row, $header, $error);
				}
			}

			if ($check === false) {
				if (is_callable($fix)) {
					$fix($data);
				}

				$error = true;
			}
		}

		if ($error === false) {
			$this->out('No problems found.', ['style' => 'primary']);
		} else if ($options['fix'] === false) {
			$this->out('Please fix the above problems.', ['style' => 'warning']);
		}

		return $result;
	}

	protected function defaultOutput($row, $header, &$error)
	{
		if ($error === false) {
			$this->out($header, ['style' => 'primary']);
			$error = true;
		}
		$this->out($row, ['style' => 'info']);
	}
}
