<?php
require dirname(__FILE__) . '/../../www/config.php';

if (nZEDb_RELEASE_SEARCH_TYPE != \ReleaseSearch::SPHINX) {
	exit('Error, nZEDb_RELEASE_SEARCH_TYPE in www/settings.php must be set to SPHINX!' . PHP_EOL);
}

if (!isset($argv[1]) || !in_array($argv[1], ['releases_rt'])) {
	exit('Argument1 is the index name, releases_rt is the only supported currently.' . PHP_EOL);
}

switch ($argv[1]) {
	case 'releases_rt':
		releases_rt();
		break;
	default:
		exit();
}

// Bulk insert releases into sphinx RT index.
function releases_rt()
{
	$pdo = new \nzedb\db\DB();
	$rows = $pdo->queryExec('SELECT id, guid, name, searchname, fromname FROM releases');

	if ($rows !== false && $rows->rowCount()) {
		$sphinx = new \SphinxSearch();

		$total = $rows->rowCount();
		$string = 'REPLACE INTO releases_rt (id, guid, name, searchname, fromname) VALUES ';
		$tempString = '';
		$i = 0;
		echo '[Starting to populate sphinx RT indexes with ' . $total . ' releases.] ';
		foreach ($rows as $row) {
			$i++;
			$tempString .= sprintf(
				'(%d, %s, %s, %s, %s),' ,
				$row['id'],
				$sphinx->sphinxQL->escapeString($row['guid']),
				$sphinx->sphinxQL->escapeString($row['name']),
				$sphinx->sphinxQL->escapeString($row['searchname']),
				$sphinx->sphinxQL->escapeString($row['fromname'])
			);
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
