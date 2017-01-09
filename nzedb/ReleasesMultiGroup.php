<?php

namespace nzedb;

use app\models\ReleasesGroups;
use app\models\Settings;
use nzedb\db\DB;
use nzedb\processing\ProcessReleases;
use nzedb\utility\Misc;


class ReleasesMultiGroup
{
	/**
	 * @var
	 */
	protected $mgrFromNames;

	/**
	 * @var NZBMultiGroup
	 */
	protected $mgrnzb;


	/**
	 * ReleasesMultiGroup constructor.
	 *
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		$this->mgrnzb = new NZBMultiGroup();
		$this->pdo = new DB();
		$this->consoleTools = new ConsoleTools(['ColorCLI' => $this->pdo->log]);
		$this->groups = new Groups(['Settings' => $this->pdo]);
		$this->releaseCleaning = new ReleaseCleaning($this->pdo);
		$this->releases = new Releases(['Settings' => $this->pdo, 'Groups' => $this->groups]);
		$this->tablePerGroup = (Settings::value('..tablepergroup') == 0 ? false : true);
		$this->echoCLI = nZEDb_ECHOCLI;
		$this->releaseCreationLimit = (Settings::value('..maxnzbsprocessed') != '' ? (int)Settings::value('..maxnzbsprocessed') : 1000);
	}

	/**
	 * @param $fromName
	 *
	 * @return bool
	 */
	public function isMultiGroup($fromName)
	{
		$array = array_column($this->getAllPosters(), 'poster');
		return in_array($fromName, $array);
	}

	/**
	 * Create releases from complete collections.
	 *
	 *
	 * @param $groupID
	 *
	 * @return array
	 * @access public
	 */
	public function createMGRReleases($groupID)
	{
		$startTime = time();

		$categorize = new Categorize(['Settings' => $this->pdo]);
		$returnCount = $duplicate = 0;

		if ($this->echoCLI) {
			$this->pdo->log->doEcho($this->pdo->log->header("Process Releases -> Create releases from complete MGR collections."));
		}

		$this->pdo->ping(true);

		$collections = $this->pdo->queryDirect(
			sprintf('
				SELECT SQL_NO_CACHE mgr_collections.*, groups.name AS gname
				FROM mgr_collections
				INNER JOIN groups ON mgr_collections.group_id = groups.id
				WHERE %s mgr_collections.filecheck = %d
				AND filesize > 0 LIMIT %d',
				(!empty($groupID) ? ' group_id = ' . $groupID . ' AND ' : ' '),
				ProcessReleases::COLLFC_SIZED,
				$this->releaseCreationLimit
			)
		);

		if ($this->echoCLI && $collections !== false) {
			echo $this->pdo->log->primary($collections->rowCount() . " MGR Collections ready to be converted to releases.");
		}

		if ($collections instanceof \Traversable) {
			$preDB = new PreDb(['Echo' => $this->echoCLI, 'Settings' => $this->pdo]);

			foreach ($collections as $collection) {

				$cleanRelName = $this->pdo->escapeString(
					utf8_encode(
						str_replace(['#', '@', '$', '%', '^', '§', '¨', '©', 'Ö'], '', $collection['subject'])
					)
				);
				$fromName = $this->pdo->escapeString(
					utf8_encode(trim($collection['fromname'], "'"))
				);

				// Look for duplicates, duplicates match on releases.name, releases.fromname and releases.size
				// A 1% variance in size is considered the same size when the subject and poster are the same
				$dupeCheck = $this->pdo->queryOneRow(
					sprintf("
						SELECT SQL_NO_CACHE id
						FROM releases
						WHERE name = %s
						AND fromname = %s
						AND size BETWEEN '%s'
						AND '%s'",
						$cleanRelName,
						$fromName,
						($collection['filesize'] * .99),
						($collection['filesize'] * 1.01)
					)
				);

				if ($dupeCheck === false) {

					$cleanedName = $this->releaseCleaning->releaseCleaner(
						$collection['subject'], $collection['fromname'], $collection['filesize'], $collection['gname']
					);

					if (is_array($cleanedName)) {
						$properName = $cleanedName['properlynamed'];
						$preID = (isset($cleanerName['predb']) ? $cleanerName['predb'] : false);
						$isReqID = (isset($cleanerName['requestid']) ? $cleanerName['requestid'] : false);
						$cleanedName = $cleanedName['cleansubject'];
					} else {
						$properName = true;
						$isReqID = $preID = false;
					}

					if ($preID === false && $cleanedName !== '') {
						// try to match the cleaned searchname to predb title or filename here
						$preMatch = $preDB->matchPre($cleanedName);
						if ($preMatch !== false) {
							$cleanedName = $preMatch['title'];
							$preID = $preMatch['predb_id'];
							$properName = true;
						}
					}

					$releaseID = $this->releases->insertRelease(
						[
							'name' => $cleanRelName,
							'searchname' => $this->pdo->escapeString(utf8_encode($cleanedName)),
							'totalpart' => $collection['totalfiles'],
							'groups_id' => $collection['group_id'],
							'guid' => $this->pdo->escapeString($this->releases->createGUID()),
							'postdate' => $this->pdo->escapeString($collection['date']),
							'fromname' => $fromName,
							'size' => $collection['filesize'],
							'categories_id' => $categorize->determineCategory($collection['group_id'], $cleanedName),
							'isrenamed' => ($properName === true ? 1 : 0),
							'reqidstatus' => ($isReqID === true ? 1 : 0),
							'predb_id' => ($preID === false ? 0 : $preID),
							'nzbstatus' => NZB::NZB_NONE
						]
					);

					if ($releaseID !== false) {
						// Update collections table to say we inserted the release.
						$this->pdo->queryExec(
							sprintf('
								UPDATE mgr_collections
								SET filecheck = %d, releaseid = %d
								WHERE id = %d',
								ProcessReleases::COLLFC_INSERTED,
								$releaseID,
								$collection['id']
							)
						);

						if (preg_match_all('#(\S+):\S+#', $collection['xref'], $matches)) {
							$matches = array_unique($matches[1]);
							foreach ($matches as $grp) {
								//check if the group name is in a valid format
								$grpTmp = $this->groups->isValidGroup($grp);
								if ($grpTmp !== false) {
									//check if the group already exists in database
									$xrefGrpID = $this->groups->getIDByName($grpTmp);
									if ($xrefGrpID === '') {
										$xrefGrpID = $this->groups->add(
											[
												'name'                  => $grpTmp,
												'description'           => 'Added by Release processing',
												'backfill_target'       => 1,
												'first_record'          => 0,
												'last_record'           => 0,
												'active'                => 0,
												'backfill'              => 0,
												'minfilestoformrelease' => '',
												'minsizetoformrelease'  => ''
											]
										);
									}

									$relGroups = ReleasesGroups::create(
										[
											'releases_id' => $releaseID,
											'groups_id'   => $xrefGrpID,
										]
									);
									$relGroups->save();
								}
							}
						}

						$returnCount++;

						if ($this->echoCLI) {
							echo "Added $returnCount releases.\r";
						}
					}
				} else {
					// The release was already in the DB, so delete the collection.
					$this->pdo->queryExec(
						sprintf('
							DELETE c, b, p FROM mgr_collections c
							INNER JOIN mgr_binaries b ON(c.id=b.collection_id)
							STRAIGHT_JOIN mgr_parts p ON(b.id=p.binaryid)
							WHERE c.collectionhash = %s',
							$this->pdo->escapeString($collection['collectionhash'])
						)
					);
					$duplicate++;
				}
			}
		}

		if ($this->echoCLI) {
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
					PHP_EOL .
					number_format($returnCount) .
					' Releases added and ' .
					number_format($duplicate) .
					' duplicate collections deleted in ' .
					$this->consoleTools->convertTime(time() - $startTime)
				), true
			);
		}

		return ['added' => $returnCount, 'dupes' => $duplicate];
	}

	/**
	 * Create NZB files from complete releases.
	 *
	 *
	 * @param $groupID
	 *
	 * @return int
	 * @access public
	 */
	public function createMGRNZBs($groupID)
	{
		$this->mgrFromNames = Misc::convertMultiArray($this->getAllPosters(), "','");

		$releases = $this->pdo->queryDirect(
			sprintf("
				SELECT SQL_NO_CACHE CONCAT(COALESCE(cp.title,'') , CASE WHEN cp.title IS NULL THEN '' ELSE ' > ' END , c.title) AS title,
					r.name, r.id, r.guid
				FROM releases r
				INNER JOIN categories c ON r.categories_id = c.id
				INNER JOIN categories cp ON cp.id = c.parentid
				WHERE %s r.nzbstatus = 0 AND r.fromname IN ('%s')",
				(!empty($groupID) ? ' r.groups_id = ' . $groupID . ' AND ' : ' '),
				$this->mgrFromNames
			)
		);

		$nzbCount = 0;

		if ($releases && $releases->rowCount()) {
			$total = $releases->rowCount();
			// Init vars for writing the NZB's.
			$this->mgrnzb->initiateForMgrWrite();
			foreach ($releases as $release) {

				if ($this->mgrnzb->writeMgrNZBforReleaseId($release['id'], $release['guid'], $release['name'], $release['title']) === true) {
					$nzbCount++;
					if ($this->echoCLI) {
						echo $this->pdo->log->primaryOver("Creating NZBs and deleting MGR Collections:\t" . $nzbCount . '/' . $total . "\r");
					}
				}
			}
		}

		return $nzbCount;
	}

	/**
	 * @param $poster
	 */
	public function addPoster($poster)
	{
		$this->pdo->queryInsert(sprintf('INSERT INTO mgr_posters (poster) VALUE (%s)', $this->pdo->escapeString($poster)));
	}

	/**
	 * @param $id
	 * @param $poster
	 */
	public function updatePoster($poster, $id)
	{
		$this->pdo->queryExec(sprintf('UPDATE mgr_posters SET poster = %s WHERE id = %d', $this->pdo->escapeString($poster), $id));
	}

	/**
	 * @return array|bool
	 */
	public function getAllPosters()
	{
		$result = $this->pdo->query(sprintf('SELECT poster AS poster FROM mgr_posters'));
		if (is_array($result) && !empty($result)) {
			return $result;
		}
		return false;
	}
}

