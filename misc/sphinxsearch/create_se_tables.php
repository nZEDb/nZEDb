<?php
require dirname(__FILE__) . '/../../www/config.php';

if (nZEDb_RELEASE_SEARCH_TYPE != \ReleaseSearch::SPHINX) {
	exit('Error, nZEDb_RELEASE_SEARCH_TYPE in www/settings.php must be set to SPHINX!' . PHP_EOL);
}

if (isset($argv[1]) && isset($argv[2]) && ctype_digit($argv[2])) {
        $sphinxConnection = sprintf('sphinx://%s:%d/', $argv[1], $argv[2]);
} elseif(isset($argv[1])) {
        $sphinxConnection = sprintf('unix://%s:', $argv[1]);
} else {
        exit('Argument 1 must the hostname or IP to the Sphinx searchd server, Argument 2 must be the port to the Sphinx searchd server.
Alternatively, Argument 1 can be a unix domain socket.' . PHP_EOL);
}

$pdo = new \nzedb\db\DB();

$tables = [];
$tables['releases_se'] =
sprintf(
"CREATE TABLE releases_se
(
	id          BIGINT UNSIGNED NOT NULL,
	weight      INTEGER NOT NULL,
	query       VARCHAR(1024) NOT NULL,
	guid        VARCHAR(40) NOT NULL,
	name        VARCHAR(255) NOT NULL DEFAULT '',
	searchname  VARCHAR(255) NOT NULL DEFAULT '',
	fromname    VARCHAR(255) NULL,
	INDEX(query)
) ENGINE=SPHINX CONNECTION=\"%sreleases_rt\"",
$sphinxConnection
);

foreach ($tables as $table => $query) {
	$pdo->queryExec(sprintf('DROP TABLE IF EXISTS %s', $table));
	$pdo->queryExec($query);
}

echo 'All done! If you messed up your sphinx connection info, you can rerun this script.' . PHP_EOL;
