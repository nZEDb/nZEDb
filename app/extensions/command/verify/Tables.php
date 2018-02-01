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
use app\extensions\data\Model;


class Tables extends \app\extensions\console\Command
{
	public function __construct()
	{
		parent::__construct();
	}

	public function run()
	{
		if (empty($this->request->params['args'])) {
			return $this->_help();
		}

		foreach ($this->request->params['args'] as $arg) {
			switch ($arg) {
				case 'Settings':
					$this->tableSettings();
					break;
				case '':
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
		$output = static function($row, $header, $error) {
			if ($error === false) {
				$this->out("section, subsection, name", ['style' => 'primary']);
				$error = true;
			}
			$this->out(" {$row['section']}, {$row['subsection']}, {$row['name']}: MISSING!",
				['style' => 'info']);
		};

		$validate = static function($row) {
			$result = Settings::value(
				[
					'section'    => $row['section'],
					'subsection' => $row['subsection'],
					'name'       => $row['name']
				],
				true);
			return ($result !== null);
		};

		return $this->validate(new Settings(),
			[
				'file' => nZEDb_RES . 'db/schema/data/10-settings.tsv',
				'output' => $output,
				'test' => $validate,
			]
		);
	}

	/**
	 * @param \app\extensions\data\Model $model   The specific table object for the intended db table.
	 *                                            It must be a child of \app\extensions\data\Model
	 * @param array                      $options Settings for the validation. Some more optional than
	 *                                            others. Entries include:
	 *                                            `file`  - filename of data file to use
	 *                                            `fix`   - whether to fix failures (false|closure)
	 *                                            `output`- closure to format text output.
	 *                                            `silent - disable output (boolean)
	 *                                            `test`  - closure to use for validation.
	 *
	 * @return boolean	true if no errors found, false otherwise
	 */
	private function validate(Model $model, array $options = [])
	{
		$table = get_class($model);
		$defaults = [
			'file'		=> Text::pathCombine([
				'db',
				'schema',
				'data',
				'10-' . strtolower($table) . '.tsv'
			], nZEDb_RES),
			'fix'		=> false,
			'output'	=> null,
			'silent'	=> true,
			'test'		=> null,
		];
		$options += $defaults;
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
			var_dump($rows);
			throw new \InvalidArgumentException("File {$options['file']} did not return a list of rows!");
		}

		// Move the column names/order off of the array.
		$header = array_shift($rows);
		$columns = explode([','], $header);
		array_walk($columns, function(&$value) { $value = trim($value); });


		if ($options['silent'] != true) {
			$this->out("Verifying $table table...", ['style' => 'primary']);
		}

		$error = false;
		$result = true;
		foreach ($rows as $row) {
			$data = array_combine($columns, explode("\t", $row));
			$check = $test($data, $columns);
			if ($check === false) {
				if ($options['silent'] === false) {
					if (is_callable($output)) {
						$output($data, $header, $error);
					} else {
						$this->defaultOutput($row, $header, $error);
					}
				}

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

	public static function hasAllEntry($console = null)
	{
		if ($dummy !== null) {
			if ($console !== null) {
				$console->primary("Verifying settings table...");
				$console->info("(section, subsection, name):");
			}
			$result = true;
			foreach ($settings as $line) {
				$message = '';
				list($setting['section'], $setting['subsection'], $setting['name']) =
					explode("\t", $line);

				$value = Settings::value(
					[
						'section'    => $setting['section'],
						'subsection' => $setting['subsection'],
						'name'       => $setting['name']
					],
					true);
				if ($value === null) {
					$result = false;
					$message = "error";
				}

				if ($message != '' && $console !== null) {
					$console->out(" {$setting['section']}, {$setting['subsection']}, {$setting['name']}: "
						. "MISSING!");
				}
			}
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
