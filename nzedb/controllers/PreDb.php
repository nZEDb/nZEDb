<?php

/**
 * Class for inserting names/categories etc from PreDB sources into the DB,
 * also for matching names on files / subjects.
 *
 * Class PreDb
 */
Class PreDb
{
	// Nuke status.
	const PRE_NONUKE  = 0; // Pre is not nuked.
	const PRE_UNNUKED = 1; // Pre was un nuked.
	const PRE_NUKED   = 2; // Pre is nuked.
	const PRE_MODNUKE = 3; // Nuke reason was modified.
	const PRE_RENUKED = 4; // Pre was re nuked.
	const PRE_OLDNUKE = 5; // Pre is nuked for being old.

	/**
	 * @var bool|stdClass
	 */
	protected $site;

	/**
	 * @var bool
	 */
	protected $echooutput;

	/**
	 * @var nzedb\db\DB
	 */
	protected $db;

	/**
	 * @var ColorCLI
	 */
	protected $c;
	/**
	 * @param bool $echo
	 */
	public function __construct($echo = false)
	{
		$this->echooutput = ($echo && nZEDb_ECHOCLI);
		$this->db = new nzedb\db\DB();
		$this->c = new ColorCLI();
	}

	/**
	 * Attempts to match PreDB titles to releases.
	 *
	 * @param $nntp
	 */
	public function checkPre($nntp)
	{
		$consoleTools = new ConsoleTools();
		$updated = 0;
		if ($this->echooutput) {
			echo $this->c->header('Querying DB for release search names not matched with PreDB titles.');
		}

		$res = $this->db->queryDirect('
			SELECT p.id AS preid, r.id AS releaseid
			FROM predb p
			INNER JOIN releases r ON p.title = r.searchname
			WHERE r.preid = 0'
		);

		if ($res !== false) {

			$total = $res->rowCount();
			echo $this->c->primary(number_format($total) . ' releases to match.');

			if ($total > 0) {
				foreach ($res as $row) {
					$this->db->queryExec(
						sprintf('UPDATE releases SET preid = %d WHERE id = %d', $row['preid'], $row['releaseid'])
					);

					if ($this->echooutput) {
						$consoleTools->overWritePrimary(
							'Matching up preDB titles with release searchnames: ' . $consoleTools->percentString( ++$updated, $total)
						);
					}
				}
				if ($this->echooutput) {
					echo PHP_EOL;
				}
			}

			if ($this->echooutput) {
				echo $this->c->header(
					'Matched ' . number_format(($updated > 0) ? $updated : 0) . ' PreDB titles to release search names.'
				);
			}
		}
	}

	/**
	 * Try to match a single release to a PreDB title when the release is created.
	 *
	 * @param string $cleanerName
	 *
	 * @return array Empty if not found, title/ID from PreDB if found.
	 */
	public function matchPre($cleanerName)
	{
		if ($cleanerName == '') {
			return array();
		}

		$titleCheck = $this->db->queryOneRow(
			sprintf('SELECT id FROM predb WHERE title = %s', $this->db->escapeString($cleanerName))
		);

		if ($titleCheck !== false) {
			return array(
				'title' => $cleanerName,
				'preid' => $titleCheck['id']
			);
		}

		// Check if clean name matches a PreDB filename.
		$fileCheck = $this->db->queryOneRow(
			sprintf('SELECT id, title FROM predb WHERE filename = %s', $this->db->escapeString($cleanerName))
		);

		if ($fileCheck !== false) {
			return array(
				'title' => $fileCheck['title'],
				'preid' => $fileCheck['id']
			);
		}

		return array();
	}

	/**
	 * Matches the hashes within the predb table to release files and subjects (names) which are hashed.
	 *
	 * @param $time
	 * @param $echo
	 * @param $cats
	 * @param $namestatus
	 * @param $show
	 *
	 * @return int
	 */
	public function parseTitles($time, $echo, $cats, $namestatus, $show)
	{
		$namefixer = new NameFixer($this->echooutput);
		$consoletools = new ConsoleTools();
		$updated = $checked = 0;
		$matches = '';

		$tq = '';
		if ($time == 1) {
			$tq = 'AND r.adddate > (NOW() - INTERVAL 3 HOUR) ORDER BY rf.releaseid, rf.size DESC';
		}
		$ct = '';
		if ($cats == 1) {
			$ct = 'AND r.categoryid IN (1090, 2020, 3050, 6050, 5050, 7010, 8050)';
		}

		if ($this->echooutput) {
			$te = '';
			if ($time == 1) {
				$te = ' in the past 3 hours';
			}
			echo $this->c->header('Fixing search names' . $te . " using the predb hash.");
		}
		$regex = "AND (r.ishashed = 1 OR rf.ishashed = 1)";

		if ($cats === 3) {
			$query = sprintf('SELECT r.id AS releaseid, r.name, r.searchname, r.categoryid, r.groupid, '
				. 'dehashstatus, rf.name AS filename FROM releases r '
				. 'LEFT OUTER JOIN releasefiles rf ON r.id = rf.releaseid '
				. 'WHERE nzbstatus = 1 AND dehashstatus BETWEEN -6 AND 0 AND preid = 0 %s', $regex);
		} else {
			$query = sprintf('SELECT r.id AS releaseid, r.name, r.searchname, r.categoryid, r.groupid, '
				. 'dehashstatus, rf.name AS filename FROM releases r '
				. 'LEFT OUTER JOIN releasefiles rf ON r.id = rf.releaseid '
				. 'WHERE nzbstatus = 1 AND isrenamed = 0 AND dehashstatus BETWEEN -6 AND 0 %s %s %s', $regex, $ct, $tq);
		}

		$res = $this->db->queryDirect($query);
		$total = $res->rowCount();
		echo $this->c->primary(number_format($total) . " releases to process.");
		if ($total > 0) {
			foreach ($res as $row) {
				if (preg_match('/[a-fA-F0-9]{32,40}/i', $row['name'], $matches)) {
					$updated = $updated + $namefixer->matchPredbHash($matches[0], $row, $echo, $namestatus, $this->echooutput, $show);
				} else if (preg_match('/[a-fA-F0-9]{32,40}/i', $row['filename'], $matches)) {
					$updated = $updated + $namefixer->matchPredbHash($matches[0], $row, $echo, $namestatus, $this->echooutput, $show);
				}
				if ($show === 2) {
					$consoletools->overWritePrimary("Renamed Releases: [" . number_format($updated) . "] " . $consoletools->percentString(++$checked, $total));
				}
			}
		}
		if ($echo == 1) {
			echo $this->c->header("\n" . $updated . " releases have had their names changed out of: " . number_format($checked) . " files.");
		} else {
			echo $this->c->header("\n" . $updated . " releases could have their names changed. " . number_format($checked) . " files were checked.");
		}

		return $updated;
	}

	/**
	 * Get all PRE's in the DB.
	 *
	 * @param int    $offset  OFFSET
	 * @param int    $offset2 LIMIT
	 * @param string $search  Optional title search.
	 *
	 * @return array The row count and the query results.
	 */
	public function getAll($offset, $offset2, $search = '')
	{
		if ($search !== '') {
			$search = explode(' ', trim($search));
			if (count($search > 1)) {
				$search = "LIKE '%" . implode("%' AND title LIKE '%", $search) . "%'";
			} else {
				$search = "LIKE '%" . $search . "%'";
			}
			$search = 'WHERE title ' . $search;
			$count = $this->db->queryOneRow(sprintf('SELECT COUNT(*) AS cnt FROM predb %s', $search));
			$count = $count['cnt'];
		} else {
			$count = $this->getCount();
		}

		$parr = $this->db->query(
			sprintf('
				SELECT p.*, r.guid
				FROM predb p
				LEFT OUTER JOIN releases r ON p.id = r.preid %s
				ORDER BY p.predate DESC LIMIT %d OFFSET %d',
				$search,
				$offset2,
				$offset
			)
		);
		return array('arr' => $parr, 'count' => $count);
	}

	/**
	 * Get count of all PRE's.
	 *
	 * @return int
	 */
	public function getCount()
	{
		$count = $this->db->queryOneRow('SELECT COUNT(*) AS cnt FROM predb');
		return ($count === false ? 0 : $count['cnt']);
	}

	/**
	 * Get all PRE's for a release.
	 *
	 * @param int $preID
	 *
	 * @return array
	 */
	public function getForRelease($preID)
	{
		return $this->db->query(sprintf('SELECT * FROM predb WHERE id = %d', $preID));
	}

	/**
	 * Return a single PRE for a release.
	 *
	 * @param int $preID
	 *
	 * @return array
	 */
	public function getOne($preID)
	{
		return $this->db->queryOneRow(sprintf('SELECT * FROM predb WHERE id = %d', $preID));
	}

}
