<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$consoletools = new \ConsoleTools(['ColorCLI' => $pdo->log]);
$ran = false;

if (isset($argv[1]) && $argv[1] === "all") {
	if (isset($argv[2]) && $argv[2] === "true") {
		$ran = true;
		$where = '';
		if (isset($argv[3]) && $argv[3] === "truncate") {
			echo "Truncating tables\n";
			$pdo->queryExec("TRUNCATE TABLE consoleinfo");
			$pdo->queryExec("TRUNCATE TABLE gamesinfo");
			$pdo->queryExec("TRUNCATE TABLE movieinfo");
			$pdo->queryExec("TRUNCATE TABLE releasevideo");
			$pdo->queryExec("TRUNCATE TABLE musicinfo");
			$pdo->queryExec("TRUNCATE TABLE bookinfo");
			$pdo->queryExec("TRUNCATE TABLE releasenfo");
			$pdo->queryExec("TRUNCATE TABLE releaseextrafull");
			$pdo->queryExec("TRUNCATE TABLE xxxinfo");
		}
		echo $pdo->log->header("Resetting all postprocessing");
		$qry = $pdo->queryDirect("SELECT id FROM releases");
		$affected = 0;
		if ($qry instanceof \Traversable) {
			$total = $qry->rowCount();
			foreach ($qry as $releases) {
				$pdo->queryExec(
					sprintf("
						UPDATE releases
						SET consoleinfoid = NULL, gamesinfo_id = 0, imdbid = NULL, musicinfoid = NULL,
							bookinfoid = NULL, rageid = -1, xxxinfo_id = 0, passwordstatus = -1, haspreview = -1,
							jpgstatus = 0, videostatus = 0, audiostatus = 0, nfostatus = -1
						WHERE id = %d",
						$releases['id']
					)
				);
				$consoletools->overWritePrimary("Resetting Releases:  " . $consoletools->percentString(++$affected, $total));
			}
		}
	}
}
if (isset($argv[1]) && ($argv[1] === "consoles" || $argv[1] === "all")) {
	$ran = true;
	if (isset($argv[3]) && $argv[3] === "truncate") {
		$pdo->queryExec("TRUNCATE TABLE consoleinfo");
	}
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all Console postprocessing");
		$where = ' WHERE consoleinfoid IS NOT NULL';
	} else {
		echo $pdo->log->header("Resetting all failed Console postprocessing");
		$where = " WHERE consoleinfoid IN (-2, 0) AND categoryid BETWEEN 1000 AND 1999";
	}

	$qry = $pdo->queryDirect("SELECT id FROM releases" . $where);
	if ($qry !== false) {
		$total = $qry->rowCount();
	} else {
		$total = 0;
	}
	$concount = 0;
	if ($qry instanceof \Traversable) {
		foreach ($qry as $releases) {
			$pdo->queryExec("UPDATE releases SET consoleinfoid = NULL WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting Console Releases:  " . $consoletools->percentString(++$concount, $total));
		}
	}
	echo $pdo->log->header("\n" . number_format($concount) . " consoleinfoID's reset.");
}
if (isset($argv[1]) && ($argv[1] === "games" || $argv[1] === "all")) {
	$ran = true;
	if (isset($argv[3]) && $argv[3] === "truncate") {
		$pdo->queryExec("TRUNCATE TABLE gamesinfo");
	}
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all Games postprocessing");
		$where = ' WHERE gamesinfo_id != 0';
	} else {
		echo $pdo->log->header("Resetting all failed Games postprocessing");
		$where = " WHERE gamesinfo_id IN (-2, 0) AND categoryid = 4050";
	}

	$qry      = $pdo->queryDirect("SELECT id FROM releases" . $where);
	if ($qry !== false) {
		$total = $qry->rowCount();
	} else {
		$total = 0;
	}
	$concount = 0;
	if ($qry instanceof \Traversable) {
		foreach ($qry as $releases) {
			$pdo->queryExec("UPDATE releases SET gamesinfo_id = 0 WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting Games Releases:  " .	$consoletools->percentString(++$concount, $total));
		}
		echo $pdo->log->header("\n" . number_format($concount) . " gameinfo_ID's reset.");
	}
}
if (isset($argv[1]) && ($argv[1] === "movies" || $argv[1] === "all")) {
	$ran = true;
	if (isset($argv[3]) && $argv[3] === "truncate") {
		$pdo->queryExec("TRUNCATE TABLE movieinfo");
	}
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all Movie postprocessing");
		$where = ' WHERE imdbid IS NOT NULL';
	} else {
		echo $pdo->log->header("Resetting all failed Movie postprocessing");
		$where = " WHERE imdbid IN (-2, 0) AND categoryid BETWEEN 2000 AND 2999";
	}

	$qry      = $pdo->queryDirect("SELECT id FROM releases" . $where);
	if ($qry !== false) {
		$total = $qry->rowCount();
	} else {
		$total = 0;
	}
	$concount = 0;
	if ($qry instanceof \Traversable) {
		foreach ($qry as $releases) {
			$pdo->queryExec("UPDATE releases SET imdbid = NULL WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting Movie Releases:  " . $consoletools->percentString(++$concount, $total));
		}
	}
	echo $pdo->log->header("\n" . number_format($concount) . " imdbID's reset.");
}
if (isset($argv[1]) && ($argv[1] === "music" || $argv[1] === "all")) {
	$ran = true;
	if (isset($argv[3]) && $argv[3] === "truncate") {
		$pdo->queryExec("TRUNCATE TABLE musicinfo");
	}
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all Music postprocessing");
		$where = ' WHERE musicinfoid IS NOT NULL';
	} else {
		echo $pdo->log->header("Resetting all failed Music postprocessing");
		$where = " WHERE musicinfoid IN (-2, 0) AND categoryid BETWEEN 3000 AND 3999";
	}

	$qry = $pdo->queryDirect("SELECT id FROM releases" . $where);
	$total = $qry->rowCount();
	$concount = 0;
	if ($qry instanceof \Traversable) {
		foreach ($qry as $releases) {
			$pdo->queryExec("UPDATE releases SET musicinfoid = NULL WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting Music Releases:  " .	$consoletools->percentString(++$concount, $total));
		}
	}
	echo $pdo->log->header("\n" . number_format($concount) . " musicinfoID's reset.");
}
if (isset($argv[1]) && ($argv[1] === "misc" || $argv[1] === "all")) {
	$ran = true;
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all Additional postprocessing");
		$where = ' WHERE (haspreview != -1 AND haspreview != 0) OR (passwordstatus != -1 AND passwordstatus != 0) OR jpgstatus != 0 OR videostatus != 0 OR audiostatus != 0';
	} else {
		echo $pdo->log->header("Resetting all failed Additional postprocessing");
		$where = " WHERE haspreview < -1 OR haspreview = 0 OR passwordstatus < -1 OR passwordstatus = 0 OR jpgstatus < 0 OR videostatus < 0 OR audiostatus < 0";
	}

	echo $pdo->log->primary("SELECT id FROM releases" . $where);
	$qry      = $pdo->queryDirect("SELECT id FROM releases" . $where);
	if ($qry !== false ) {
		$total = $qry->rowCount();
	} else {
		$total = 0;
	}
	$concount = 0;
	if ($qry instanceof \Traversable) {
		foreach ($qry as $releases) {
			$pdo->queryExec("UPDATE releases SET passwordstatus = -1, haspreview = -1, jpgstatus = 0, videostatus = 0, audiostatus = 0 WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting Releases:  " . $consoletools->percentString(++$concount, $total));
		}
	}
	echo $pdo->log->header("\n" . number_format($concount) . " Release's reset.");
}
if (isset($argv[1]) && ($argv[1] === "tv" || $argv[1] === "all")) {
	$ran = true;
	if (isset($argv[3]) && $argv[3] === "truncate") {
		$pdo->queryExec("TRUNCATE TABLE tvrage");
	}
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all TV postprocessing");
		$where = '  WHERE rageid != -1';
	} else {
		echo $pdo->log->header("Resetting all failed TV postprocessing");
		$where = " WHERE rageid IN (-2, 0) OR rageid IS NULL AND categoryid BETWEEN 5000 AND 5999";
	}

	$qry      = $pdo->queryDirect("SELECT id FROM releases" . $where);
	if ($qry !== false ) {
		$total = $qry->rowCount();
	} else {
		$total = 0;
	}
	$concount = 0;
	if ($qry instanceof \Traversable) {
		foreach ($qry as $releases) {
			$pdo->queryExec("UPDATE releases SET rageid = -1 WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting TV Releases:  " . $consoletools->percentString(++$concount, $total));
		}
	}
	echo $pdo->log->header("\n" . number_format($concount) . " rageID's reset.");
}
if (isset($argv[1]) && ($argv[1] === "books" || $argv[1] === "all")) {
	$ran = true;
	if (isset($argv[3]) && $argv[3] === "truncate") {
		$pdo->queryExec("TRUNCATE TABLE bookinfo");
	}
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all Book postprocessing");
		$where = ' WHERE bookinfoid IS NOT NULL';
	} else {
		echo $pdo->log->header("Resetting all failed Book postprocessing");
		$where = " WHERE bookinfoid IN (-2, 0) AND categoryid BETWEEN 8000 AND 8999";
	}

	$qry = $pdo->queryDirect("SELECT id FROM releases" . $where);
	$total = $qry->rowCount();
	$concount = 0;
	if ($qry instanceof \Traversable) {
		foreach ($qry as $releases) {
			$pdo->queryExec("UPDATE releases SET bookinfoid = NULL WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting Book Releases:  " . $consoletools->percentString(++$concount, $total));
		}
	}
	echo $pdo->log->header("\n" . number_format($concount) . " bookinfoID's reset.");
}
if (isset($argv[1]) && ($argv[1] === "xxx" || $argv[1] === "all")) {
	$ran = true;
	if (isset($argv[3]) && $argv[3] === "truncate") {
		$pdo->queryExec("TRUNCATE TABLE xxxinfo");
	}
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all XXX postprocessing");
		$where = ' WHERE xxxinfo_id != 0';
	} else {
		echo $pdo->log->header("Resetting all failed XXX postprocessing");
		$where = " WHERE xxxinfo_id IN (-2, 0) AND categoryid BETWEEN 6000 AND 6040";
	}

	$qry = $pdo->queryDirect("SELECT id FROM releases" . $where);
	$concount = 0;
	if ($qry instanceof \Traversable) {
		$total = $qry->rowCount();
		foreach ($qry as $releases) {
			$pdo->queryExec("UPDATE releases SET xxxinfo_id = 0 WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting XXX Releases:  " . $consoletools->percentString(++$concount,
					$total));
		}
	}
	echo $pdo->log->header("\n" . number_format($concount) . " xxxinfo_ID's reset.");
}
if (isset($argv[1]) && ($argv[1] === "nfos" || $argv[1] === "all")) {
	$ran = true;
	if (isset($argv[3]) && $argv[3] === "truncate") {
		$pdo->queryExec("TRUNCATE TABLE releasenfo");
	}
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all NFO postprocessing");
		$where = ' WHERE nfostatus != -1';
	} else {
		echo $pdo->log->header("Resetting all failed NFO postprocessing");
		$where = " WHERE nfostatus < -1";
	}

	$qry = $pdo->queryDirect("SELECT id FROM releases" . $where);
	$concount = 0;
	if ($qry instanceof \Traversable) {
		$total = $qry->rowCount();
		foreach ($qry as $releases) {
			$pdo->queryExec("UPDATE releases SET nfostatus = -1 WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting NFO Releases:  " . $consoletools->percentString(++$concount, $total));
		}
	}
	echo $pdo->log->header("\n" . number_format($concount) . " NFO's reset.");
}

if ($ran === false) {
	exit(
		$pdo->log->error(
			"\nThis script will reset postprocessing per category. It can also truncate the associated tables."
			. "\nTo reset only those that have previously failed, those without covers, samples, previews, etc. use the "
			. "second argument false.\n"
			. "To reset even those previously post processed, use the second argument true.\n"
			. "To truncate the associated table, use the third argument truncate.\n\n"
			. "php reset_postprocessing.php consoles true    ...: To reset all consoles.\n"
			. "php reset_postprocessing.php games true       ...: To reset all games.\n"
			. "php reset_postprocessing.php movies true      ...: To reset all movies.\n"
			. "php reset_postprocessing.php music true       ...: To reset all music.\n"
			. "php reset_postprocessing.php misc true        ...: To reset all misc.\n"
			. "php reset_postprocessing.php tv true          ...: To reset all tv.\n"
			. "php reset_postprocessing.php books true       ...: To reset all books.\n"
			. "php reset_postprocessing.php xxx true         ...: To reset all xxx.\n"
			. "php reset_postprocessing.php nfos true        ...: To reset all nfos.\n"
			. "php reset_postprocessing.php all true         ...: To reset everything.\n"
		)
	);
} else {
	echo "\n";
}
