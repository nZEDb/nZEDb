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

namespace zed\database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use nzedb\utility\Misc;
use zed\Nzedb;


class MigrationWithLoadData extends Migration
{
	public function isDbLocal() : bool
	{
		switch (true) {
			case empty(env('DB_SOCKET', '')):
			case env('DB_HOST', '127.0.0.1') == '127.0.0.1':
			case env('DB_HOST') == 'localhost':
				$local = false;
				break;

			case preg_match_all('/inet' . '6?' . ' addr: ?([^ ]+)/', `ifconfig`, $ip_list):
				// Check for dotted quad - if one exists compare it against local IP number(s)
				if (preg_match('#^\d+\.\d+\.\d+\.\d+$#', $this->host)) {
					if (\in_array($this->host, $ip_list[1], false)) {
						$local = false;
					}
				}
				break;

			default:
				$local = true;
		}

		return $local;
	}

	public function loadDataInfile(array $options = []) : void
	{
		$defaults = [
			'enclosedby'	=> null,
			'filepath'		=> '',
		];
		$options += $defaults;


		$regex = '#^' . Misc::PATH_REGEX . '(?P<order>\d+)-(?P<table>\w+)\.tsv$#';
		$file = $options['filepath'];

		if (preg_match($regex, $file, $matches) && \is_readable($options['filepath'])) {
			/** @var string $table */
			$table = $matches['table'];

			$handle = @fopen($file, 'r');

			if (\is_resource($handle)) {
				// Get the first line of the file which holds the columns used.
				$line = fgets($handle);
				fclose($handle);

				if ($line === false) {
					throw new \ErrorException("FAILED reading first line of '$file'");
				} else {
					$fields = trim($line);
					$target = '/tmp/load.infile';

					\copy($file, $target);

					// Local keyword takes the file from the client's filesystem, not the server's
					$local = $this->isDbLocal() ? '' : 'LOCAL ';
					$enclosedby = empty($options['enclosedby']) ? '' : 'OPTIONALLY ENCLOSED BY "' . $options['enclosedby'] . '"';

					if (Nzedb::DEBUG || Misc::isCLI()) {
						echo "Inserting: $table's data into table: ...";
					}

					$rowCount = DB::connection()->getpdo()->exec(
						"LOAD DATA $local INFILE $target
							INTO TABLE $table
							FIELDS TERMINATED BY '\\t' $enclosedby
							LINES TERMINATED BY '\\r\\n'
							IGNORE 1 LINES ($fields)"
					);

					if (Nzedb::DEBUG || Misc::isCLI()) {
						echo " $rowCount rows affected\n";
					}

					\unlink($target);
				}
			} else {
				throw new \ErrorException("Failed to open file: '$file'\n");
			}
		}
	}
}
