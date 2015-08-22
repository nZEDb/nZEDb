<?php
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\ReleaseSearch;
use nzedb\SphinxSearch;
use nzedb\db\DB;

if (nZEDb_RELEASE_SEARCH_TYPE != ReleaseSearch::SPHINX) {
	exit('Error, nZEDb_RELEASE_SEARCH_TYPE in nzedb/config/settings.php must be set to SPHINX!' . PHP_EOL);
} else if (!isset($argv[1]) || !in_array($argv[1], ['releases_rt', 'release_files_rt'])) {
	exit('Argument1 is the index name, releases_rt/release_files_rt are the only supported ones currently.' . PHP_EOL);
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
			$rows = $pdo->queryExec('SELECT id, guid, name, searchname, fromname FROM releases');
			$rtvalues = '(id, guid, name, searchname, fromname)';
			break;
		case 'release_files_rt':
			$rows = $pdo->queryExec('SELECT id, releaseid, name FROM release_files');
			$rtvalues = '(id, releaseid, name)';
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
						$sphinx->sphinxQL->escapeString($row['guid']),
						$sphinx->sphinxQL->escapeString($row['name']),
						$sphinx->sphinxQL->escapeString($row['searchname']),
						$sphinx->sphinxQL->escapeString($row['fromname'])
					);
					break;
				case 'release_files_rt':
					$tempString .= sprintf(
						'(%d, %d, %s),',
						$row['id'],
						$sphinx->sphinxQL->escapeString($row['releaseid']),
						$sphinx->sphinxQL->escapeString($row['name'])
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
