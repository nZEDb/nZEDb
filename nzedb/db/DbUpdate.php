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
namespace nzedb\db;

use nzedb\utility;

/*
 * Putting procedural stuff inside class scripts like this is BAD. Do not use this as an excuse to do more.
 * This is a temporary measure until a proper frontend for cli stuff can be implemented with li3.
 */
if (!defined('nZEDb_INSTALLER')) {
	require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'config.php';

	if (\nzedb\utility\Utility::isCLI() && isset($argc) && $argc > 1 && isset($argv[1]) &&
		$argv[1] == true
	) {

		$backup  = (isset($argv[2]) && $argv[2] == 'safe') ? true : false;
		$updater = new DbUpdate(['backup' => $backup]);
		echo $updater->log->header("Db updater starting ...");
		$patched = $updater->processPatches(['safe' => $backup]);

		if ($patched > 0) {
			echo $updater->log->info("$patched patch(es) applied.");

			$smarty = new \Smarty();
			$cleared = $smarty->clearCompiledTemplate();
			if ($cleared) {
				$msg = "The smarty template cache has been cleaned for you\n";
			} else {
				$msg = "You should clear your smarty template cache at: " . SMARTY_DIR . "templates_c\n";
			}
			$updater->log->info($msg);
		}
	}
}

class DbUpdate
{
	/**
	 * @var object	Instance variable for DB object.
	 */
	public $db;

	/**
	 * @var object    Instance variable for logging object. Currently only ColorCLI supported,
	 * but expanding for full logging with agnostic API planned.
	 */
	public $log;

	/**
	 * @var object	Instance object for sites/settings class.
	 */
	public $settings;

	protected $_DbSystem;

	/**
	 * @var bool    Has the Db been backed up?
	 */
	private $backedUp = false;

	/**
	 * @var bool    Should we perform a backup?
	 */
	private $backup = false;

	public function __construct(array $options = [])
	{
		$defaults = array(
			'backup'	=> true,
			'db'		=> new \nzedb\db\DB(),
			'logger'	=> new \ColorCLI(),
		);
		$options += $defaults;
		unset($defaults);

		$this->backup	= $options['backup'];
		$this->db		= $options['db'];
		$this->log		= $options['logger'];

		$this->_DbSystem = strtolower($this->db->dbSystem());
	}

	public function loadTables(array $options = [])
	{
		$defaults = array(
			'ext'	=> 'tsv',
			'files' => array(),
			'path'	=> nZEDb_RES . 'db' . DS . 'schema' . DS . 'data',
			'regex'	=> '#^' . utility\Utility::PATH_REGEX . "(?P<order>\d+)-(?<table>\w+)\.tsv$#",
		);
		$options += $defaults;

		$files = empty($options['files']) ? \nzedb\utility\Utility::getDirFiles($options) : $options['files'];
		sort($files, SORT_NATURAL);
		$sql = 'LOAD DATA INFILE "%s" IGNORE INTO TABLE `%s` FIELDS TERMINATED BY "\t" OPTIONALLY ENCLOSED BY "\"" IGNORE 1 LINES (%s)';
		foreach ($files as $file) {
			echo "File: $file\n";

			if (is_readable($file)) {
				if (preg_match($options['regex'], $file, $matches)) {
					$table = $matches['table'];
					// Get the first line of the file which holds the columns used.
					$handle = @fopen($file, "r");
					if (is_resource($handle)) {
						$line = fgets($handle);
						fclose($handle);
						if ($line === false) {
							echo "FAILED reading first line of '$file'\n";
							continue;
						}
						$fields = trim($line);

						echo "Inserting data into table: '$table'\n";
						$this->db->exec(sprintf($sql, $file, $table, $fields));
					} else {
						exit("Failed to open file: '$file'\n");
					}
				} else {
					echo "Incorrectly formatted filename '$file' (should match " .
						 str_replace('#', '', $options['regex']) .  "\n";
				}
			} else {
				echo $this->log->error("  Unable to read file: '$file'");
			}
		}
	}

	public function processPatches(array $options = [])
	{
		$patched = 0;
		$defaults = array(
			'data'	=> nZEDb_RES . 'db' . DS . 'schema' . DS . 'data' . DS,
			'ext'   => 'sql',
			'path'  => nZEDb_RES . 'db' . DS . 'patches' . DS . $this->_DbSystem,
			'regex' => '#^' . utility\Utility::PATH_REGEX . "(?P<date>\d{4}-\d{2}-\d{2})_(?P<patch>\d+)_(?P<table>\w+)\.sql$#",
			'safe'	=> true,
		);
		$options += $defaults;

		if ($options['safe']) {
			$this->_backupDb();
		}

		$this->_useSettings();
		$currentVersion = $this->settings->getSetting('sqlpatch');
		if (!is_numeric($currentVersion)) {
			exit();
		}

		$files = empty($options['files']) ? \nzedb\utility\Utility::getDirFiles($options) : $options['files'];

		if (count($files)) {
			sort($files);
			$local = $this->db->isLocalDb() ? '' : 'LOCAL ';
			$data = $options['data'];
			echo $this->log->primary('Looking for unprocessed patches...');
			foreach($files as $file) {
				$patch = '';
				$setPatch = false;
				$fp = fopen($file, 'r');
				$patch = fread($fp, filesize($file));

				if (preg_match($options['regex'], str_replace('\\', '/', $file), $matches) && $matches['patch'] > 9) {
						$patch = $matches['patch'];
						$setPatch = true;
				} else if (preg_match("/UPDATE `?site`? SET `?value`? = '?(?P<patch>\d+)'? WHERE `?setting`? = 'sqlpatch'/i", $patch, $matches)) {
					$patch = $matches['patch'];
				} else {
					throw new \RuntimeException("No patch information available, stopping!!");
				}

				if ($patch > $currentVersion) {
					echo $this->log->header('Processing patch file: ' . $file);
					if ($options['safe'] && !$this->backedUp) {
						$this->backupDb();
					}
					$this->splitSQL($file, ['local' => $local, 'data' => $data]);
					if ($setPatch) {
						$this->db->queryExec("UPDATE settings SET value = '$patch' WHERE setting = 'sqlpatch';");
					}
					$patched++;
				}
			}
		} else {
			exit($this->log->error("\nHave you changed the path to the patches folder, or do you have the right permissions?\n"));
		}

		if ($patched === 0) {
			echo $this->log->info("Nothing to patch, you are already on version $currentVersion");
		}
		return $patched;
	}

	public function processSQLFile(array $options = [])
	{
		$defaults = array(
			'filepath'	=> nZEDb_RES . 'db' . DS . 'schema' . DS . $this->_DbSystem . '-ddl.sql',
		);
		$options += $defaults;

		$sql = file_get_contents($options['filepath']);
		$sql = str_replace(array('DELIMITER $$', 'DELIMITER ;', ' $$'), '', $sql);
		$this->db->exec($sql);
	}

	public function splitSQL($file, array $options = [])
	{
		$defaults = array(
			'data'		=> null,
			'delimiter' => ';',
			'local'		=> null,
		);
		$options += $defaults;

		if (!empty($options['vars'])) {
			extract($options['vars']);
		}

		set_time_limit(0);

		if (is_file($file)) {
			$file = fopen($file, 'r');

			if (is_resource($file)) {
				$query = array();

				while (!feof($file)) {
					$query[] = fgets($file);

					if (preg_match('~' . preg_quote($options['delimiter'], '~') . '\s*$~iS',
								   end($query)) == 1) {
						$query = trim(implode('', $query));
						if ($options['local'] !== null) {
							$query = str_replace('{:local:}', $options['local'], $query);
						}
						if (!empty($options['data'])) {
							$query = str_replace('{:data:}', $options['data'], $query);
						}

						try {
							$qry = $this->db->prepare($query);
							$qry->execute();
							echo $this->log->alternateOver('SUCCESS: ') . $this->log->primary($query);
						} catch (\PDOException $e) {
							// Log the problem and the query.
							file_put_contents(
								nZEDb_LOGS . 'patcherrors.log',
								'[' . date('r') . '] [ERROR] [' . trim(preg_replace('/\s+/', ' ', $e->getMessage())) . ']' . PHP_EOL . '[' . date('r') . '] [QUERY] [' . trim(preg_replace('/\s+/', ' ', $query)) . ']' . PHP_EOL,
								FILE_APPEND
							);

							if (
								 in_array($e->errorInfo[1], array(1091, 1060, 1061, 1071, 1146)) ||
								 in_array($e->errorInfo[0], array(23505, 42701, 42703, '42P07', '42P16'))
								) {
								if ($e->errorInfo[1] == 1060) {
									echo $this->log->warning(
										"$query The column already exists - No need to worry {" . $e->errorInfo[1] . "}.\n");
								} else {
									echo $this->log->warning(
										"$query Skipped - No need to worry {" . $e->errorInfo[1] . "}.\n");
								}
							} else {
								if (preg_match('/ALTER IGNORE/i', $query)) {
									$this->db->queryExec("SET SESSION old_alter_table = 1");
									try {
										$this->db->exec($query);
										echo $this->log->alternateOver('SUCCESS: ') . $this->log->primary($query);
									} catch (PDOException $e) {
										exit($this->log->error(
											"$query Failed {" . $e->errorInfo[1] . "}\n\t" . $e->errorInfo[2]));
									}
								} else {
									exit($this->log->error(
										"$query Failed {" . $e->errorInfo[1] . "}\n\t" . $e->errorInfo[2]));
								}
							}
						}

						while (ob_get_level() > 0) {
							ob_end_flush();
						}
						flush();
					}

					if (is_string($query) === true) {
						$query = array();
					}
				}
			}
		}
	}

	protected function _backupDb()
	{
		if (\nzedb\utility\Utility::hasCommand("php5")) {
			$PHP = "php5";
		} else {
			$PHP = "php";
		}

		system("$PHP " . nZEDb_MISC . 'testing' . DS .'DB' . DS . $this->_DbSystem . 'dump_tables.php db dump');
		$this->backedup = true;
	}

	protected function _useSettings(Sites $object = null)
	{
		if ($this->settings === null) {
			$this->settings = (empty($object)) ? new Settings() : $object;
		}
	}
}

?>
