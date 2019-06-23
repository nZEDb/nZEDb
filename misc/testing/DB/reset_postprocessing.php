<?php
require_once realpath(dirname(__DIR__, 3) . '/app/config/bootstrap.php');

use nzedb\Category;
use nzedb\ConsoleTools;
use nzedb\db\DB;

$category = new Category();
$pdo = new DB();
$consoletools = new ConsoleTools(['ColorCLI' => $pdo->log]);
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
			$pdo->queryExec("TRUNCATE TABLE video_data");
			$pdo->queryExec("TRUNCATE TABLE musicinfo");
			$pdo->queryExec("TRUNCATE TABLE bookinfo");
			$pdo->queryExec("TRUNCATE TABLE release_nfos");
			$pdo->queryExec("TRUNCATE TABLE releaseextrafull");
			$pdo->queryExec("TRUNCATE TABLE xxxinfo");
			$pdo->queryExec("TRUNCATE TABLE videos");
			$pdo->queryExec("TRUNCATE TABLE videos_aliases");
			$pdo->queryExec("TRUNCATE TABLE tv_info");
			$pdo->queryExec("TRUNCATE TABLE tv_episodes");
			$pdo->queryExec("TRUNCATE TABLE anidb_info");
			$pdo->queryExec("TRUNCATE TABLE anidb_episodes");
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
						SET consoleinfo_id = NULL, gamesinfo_id = 0, imdbid = NULL, musicinfo_id = NULL,
							bookinfo_id = NULL, videos_id = 0, tv_episodes_id = 0, xxxinfo_id = 0, passwordstatus = -1, haspreview = -1,
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
		$where = ' WHERE consoleinfo_id IS NOT NULL';
	} else {
		echo $pdo->log->header("Resetting all failed Console postprocessing");
		$where = " WHERE consoleinfo_id IN (-2, 0) AND categories_id BETWEEN " . Category::GAME_ROOT . " AND " . Category::GAME_OTHER;
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
			$pdo->queryExec("UPDATE releases SET consoleinfo_id = NULL WHERE id = " . $releases['id']);
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
		$where = " WHERE gamesinfo_id IN (-2, 0) AND categories_id = " . Category::PC_GAMES;
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
			$pdo->queryExec("UPDATE releases SET gamesinfo_id = 0 WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting Games Releases:  " . $consoletools->percentString(++$concount, $total));
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
		$where = " WHERE imdbid IN (-2, 0) AND categories_id BETWEEN " . Category::MOVIE_ROOT . " AND " . Category::MOVIE_OTHER;
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
		$where = ' WHERE musicinfo_id IS NOT NULL';
	} else {
		echo $pdo->log->header("Resetting all failed Music postprocessing");
		$where = " WHERE musicinfo_id IN (-2, 0) AND categories_id BETWEEN " . Category::MUSIC_ROOT . " AND " . Category::MUSIC_OTHER;
	}

	$qry = $pdo->queryDirect("SELECT id FROM releases" . $where);
	$total = $qry->rowCount();
	$concount = 0;
	if ($qry instanceof \Traversable) {
		foreach ($qry as $releases) {
			$pdo->queryExec("UPDATE releases SET musicinfo_id = NULL WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting Music Releases:  " . $consoletools->percentString(++$concount, $total));
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
	$where .= ' AND categories_id < 1000';

	echo $pdo->log->primary("SELECT id FROM releases" . $where);
	$qry = $pdo->queryDirect("SELECT id FROM releases" . $where);
	if ($qry !== false) {
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
		$pdo->queryExec("DELETE v, va FROM videos v INNER JOIN videos_aliases va ON v.id = va.videos_id WHERE type = 0");
		$pdo->queryExec("TRUNCATE TABLE tv_info");
		$pdo->queryExec("TRUNCATE TABLE tv_episodes");
	}
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all TV postprocessing");
		$where = ' WHERE videos_id != 0 AND tv_episodes_id != 0 AND categories_id BETWEEN ' .
				Category::TV_ROOT . ' AND ' . Category::TV_OTHER;
	} else {
		echo $pdo->log->header("Resetting all failed TV postprocessing");
		$where = ' WHERE tv_episodes_id < 0 AND categories_id BETWEEN ' . Category::TV_ROOT . ' AND ' . Category::TV_OTHER;
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
			$pdo->queryExec("UPDATE releases SET videos_id = 0, tv_episodes_id = 0 WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting TV Releases:  " . $consoletools->percentString(++$concount, $total));
		}
	}
	echo $pdo->log->header("\n" . number_format($concount) . " Video ID's reset.");
}
if (isset($argv[1]) && ($argv[1] === "anime" || $argv[1] === "all")) {
	$ran = true;
	if (isset($argv[3]) && $argv[3] === "truncate") {
		$pdo->queryExec("TRUNCATE TABLE anidb_info");
		$pdo->queryExec("TRUNCATE TABLE anidb_episodes");
	}
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all Anime postprocessing");
		$where = ' WHERE categories_id = ' . Category::TV_ANIME;
	} else {
		echo $pdo->log->header('Resetting all failed Anime postprocessing');
		$where = ' WHERE anidbid BETWEEN -2 AND -1 AND categories_id = ' . Category::TV_ANIME;
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
			$pdo->queryExec("UPDATE releases SET anidbid = NULL WHERE id = " . $releases['id']);
			$consoletools->overWritePrimary("Resetting Anime Releases:  " . $consoletools->percentString(++$concount, $total));
		}
	}
	echo $pdo->log->header("\n" . number_format($concount) . " anidbID's reset.");
}
if (isset($argv[1]) && ($argv[1] === "books" || $argv[1] === "all")) {
	$ran = true;
	if (isset($argv[3]) && $argv[3] === "truncate") {
		$pdo->queryExec("TRUNCATE TABLE bookinfo");
	}
	if (isset($argv[2]) && $argv[2] === "true") {
		echo $pdo->log->header("Resetting all Book postprocessing");
		$where = ' WHERE bookinfo_id IS NOT NULL';
	} else {
		echo $pdo->log->header("Resetting all failed Book postprocessing");
		$where = ' WHERE bookinfo_id IN (-2, 0) AND categories_id BETWEEN ' . Category::BOOKS_ROOT .
				' AND ' . Category::BOOKS_UNKNOWN;
	}

	$qry = $pdo->queryDirect("SELECT id FROM releases" . $where);
	$total = $qry->rowCount();
	$concount = 0;
	if ($qry instanceof \Traversable) {
		foreach ($qry as $releases) {
			$pdo->queryExec("UPDATE releases SET bookinfo_id = NULL WHERE id = " . $releases['id']);
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
		$where = ' WHERE xxxinfo_id IN (-2, 0) AND categories_id BETWEEN ' . Category::XXX_ROOT .
				' AND ' . Category::XXX_OTHER;
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
		$pdo->queryExec("TRUNCATE TABLE release_nfos");
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
			. "php reset_postprocessing.php anime true       ...: To reset all anime.\n"
			. "php reset_postprocessing.php books true       ...: To reset all books.\n"
			. "php reset_postprocessing.php xxx true         ...: To reset all xxx.\n"
			. "php reset_postprocessing.php nfos true        ...: To reset all nfos.\n"
			. "php reset_postprocessing.php all true         ...: To reset everything.\n"
		)
	);
} else {
	echo "\n";
}
