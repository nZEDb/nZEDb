<?php
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\ReleaseSearch;
use nzedb\SphinxSearch;
use nzedb\db\DB;

if (nZEDb_RELEASE_SEARCH_TYPE != ReleaseSearch::SPHINX) {
	exit('Error, nZEDb_RELEASE_SEARCH_TYPE in nzedb/config/settings.php must be set to SPHINX!' . PHP_EOL);
} else if (!isset($argv[1]) || !in_array($argv[1], ['releases_rt'])) {
	exit('Argument1 is the index name, releases_rt are the only supported ones currently.' . PHP_EOL);
} else {
	populate_rt($argv[1]);
}

// Bulk insert releases into sphinx RT index.
function populate_rt($table = '')
{
	$pdo = new DB();

	$rows = false;

	switch ($table) {
		case 'releases_rt':
			$pdo->queryDirect('SET SESSION group_concat_max_len=8192');
			$rows = $pdo->queryExec('SELECT r.id, r.name, r.searchname, r.fromname, IFNULL(GROUP_CONCAT(rf.name SEPARATOR " "),"") filename
				FROM releases r LEFT JOIN release_files rf ON(r.id=rf.releaseid) GROUP BY r.id'
			);
			$rtvalues = '(id, name, searchname, fromname, filename)';
			break;
	}


	if ($rows !== false && $total = $rows->rowCount()) {
		$sphinx = new SphinxSearch();

		$string = sprintf('REPLACE INTO %s %s VALUES ', $table, $rtvalues);
		$tempString = '';
		$i = 0;
		echo '[Starting to populate sphinx RT index ' . $table . ' with ' . $total . ' releases.] ';
		foreach ($rows as $row) {
			$i++;
			switch ($table) {
				case 'releases_rt':
					$tempString .= sprintf(
						'(%d, %s, %s, %s, %s),',
						$row['id'],
						$sphinx->sphinxQL->escapeString($row['name']),
						$sphinx->sphinxQL->escapeString($row['searchname']),
						$sphinx->sphinxQL->escapeString($row['fromname']),
						$sphinx->sphinxQL->escapeString($row['filename'])
					);
					break;
			}
			if ($i === 1000 || $i >= $total) {
				$sphinx->sphinxQL->queryExec($string . rtrim($tempString, ','));
				$tempString = '';
				$total -= $i;
				$i = 0;
				echo '.';
			}
		}
		echo ' [Done.]' . PHP_EOL;
	} else {
		echo 'No releases in your DB or an error occurred. This will need to be resolved before you can use the search.' . PHP_EOL;
	}
}
