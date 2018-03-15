<?php
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nzedb\db\DB;
use nzedb\ReleaseSearch;
use nzedb\SphinxSearch;

if (nZEDb_RELEASE_SEARCH_TYPE != ReleaseSearch::SPHINX) {
	exit('Error, nZEDb_RELEASE_SEARCH_TYPE in nzedb/config/settings.php must be set to SPHINX!' . PHP_EOL);
} elseif (!isset($argv[1]) || !in_array($argv[1], ['releases_rt'])) {
	exit(
		"Argument 1 is the index name, releases_rt are the only supported ones currently.\n" .
		"Argument 2 is optional, max number of rows to send to sphinx at a time, 10,000 is the default if not set.\n" .
		"              The value of 10,000 is good for the default sphinx.conf max_packet_size of 8M, if you want\n" .
		"              to raise this higher than 10,000, raise the sphinx.conf max_packet_size higher than  8M.\n" .
		"              If you have many releases, raise the sphinx.conf max_packet_size to 128M (the maximum), restart sphinx and\n" .
		"              and set Argument 2 to 250,000. This will speed up the script tremendously.\n"
	);
} else {
	populate_rt($argv[1], (isset($argv[2]) && is_numeric($argv[2]) && $argv[2] > 0 ? $argv[2] : 10000));
}

// Bulk insert releases into sphinx RT index.
function populate_rt($table, $max)
{
	$pdo = new DB();

	switch ($table) {
		case 'releases_rt':
			$pdo->queryDirect('SET SESSION group_concat_max_len=8192');
			$query = (
				'SELECT r.id, r.name, r.searchname, r.fromname, IFNULL(GROUP_CONCAT(rf.name SEPARATOR " "),"") filename
				FROM releases r
				LEFT JOIN release_files rf ON(r.id=rf.releases_id)
				WHERE r.id > %d
				GROUP BY r.id
				ORDER BY r.id ASC
				LIMIT %d'
			);
			$rtvalues = '(id, name, searchname, fromname, filename)';
			$totals = $pdo->queryOneRow('SELECT COUNT(id) AS c, MIN(id) AS min FROM releases');
			if (!$totals) {
				exit("Could not get database information for releases table.\n");
			}
			$total = $totals['c'];
			$minId = $totals['min'];
			break;
		default:
			exit();
	}

	$sphinx = new SphinxSearch();
	$string = sprintf('REPLACE INTO %s %s VALUES ', $table, $rtvalues);

	$lastId = $minId - 1;
	echo "[Starting to populate sphinx RT index $table with $total releases.]\n";
	for ($i = $minId; $i <= ($total + $max + $minId); $i += $max) {
		$rows = $pdo->queryDirect(sprintf($query, $lastId, $max));
		if (!$rows) {
			continue;
		}

		$tempString = '';
		foreach ($rows as $row) {
			if ($row['id'] > $lastId) {
				$lastId = $row['id'];
			}
			switch ($table) {
				case 'releases_rt':
					$tempString .= sprintf(
						'(%d,%s,%s,%s,%s),',
						$row['id'],
						$sphinx->sphinxQL->escapeString($row['name']),
						$sphinx->sphinxQL->escapeString($row['searchname']),
						$sphinx->sphinxQL->escapeString($row['fromname']),
						$sphinx->sphinxQL->escapeString($row['filename'])
					);
					break;
			}
		}
		if (!$tempString) {
			continue;
		}
		$sphinx->sphinxQL->queryExec($string . rtrim($tempString, ','));
		echo '.';
	}
	echo "\n[Done]\n";
}
