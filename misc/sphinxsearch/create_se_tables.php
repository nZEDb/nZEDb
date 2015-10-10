<?php
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\ReleaseSearch;
use nzedb\db\DB;

if (nZEDb_RELEASE_SEARCH_TYPE != ReleaseSearch::SPHINX) {
	exit('Error, nZEDb_RELEASE_SEARCH_TYPE in nzedb/config/settings.php must be set to SPHINX!' . PHP_EOL);
}

$sphinxConnection = '';
if ($argc == 3 && is_numeric($argv[2])) {
	$sphinxConnection = sprintf('sphinx://%s:%d/', $argv[1], $argv[2]);
} elseif ($argc == 2) {
	// Checks that argv[1] exists AND that there are no other arguments, which would be an error.
	$socket = preg_replace('#^(?:unix://)?(.*)$#', '$1', $argv[1]);
	if (substr($socket, 0, 1) == '/') {
		// Make sure the socket path is fully qualified (and using correct separator).
		$sphinxConnection = sprintf('unix://%s:', $socket);
	}
} else {
	exit("Argument 1 must the hostname or IP to the Sphinx searchd server, Argument 2 must be the port to the Sphinx searchd server.\nAlternatively, Argument 1 can be a unix domain socket." . PHP_EOL);
}

$pdo = new DB();

$tableSQL_releases = <<<DDLSQL
CREATE TABLE releases_se
(
	id          BIGINT UNSIGNED NOT NULL,
	weight      INTEGER NOT NULL,
	query       VARCHAR(1024) NOT NULL,
	name        VARCHAR(255) NOT NULL DEFAULT '',
	searchname  VARCHAR(255) NOT NULL DEFAULT '',
	fromname    VARCHAR(255) NULL,
	filename    VARCHAR(1000) NULL,
	INDEX(query)
) ENGINE=SPHINX CONNECTION="%sreleases_rt"
DDLSQL;

$tables                     = [];
$tables['releases_se']      = sprintf($tableSQL_releases, $sphinxConnection);

foreach ($tables as $table => $query) {
	$pdo->queryExec(sprintf('DROP TABLE IF EXISTS %s', $table));
	$pdo->queryExec($query);
}

echo 'All done! If you messed up your sphinx connection info, you can rerun this script.' . PHP_EOL;
