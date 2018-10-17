<?php
namespace nzedb\processing;

use app\models\Groups as Group;
use app\models\MultigroupPosters;
use app\models\ReleasesGroups;
use app\models\Settings;
use app\models\Tables;
use nzedb\Categorize;
use nzedb\Category;
use nzedb\ConsoleTools;
use nzedb\Genres;
use nzedb\Groups;
use nzedb\NZB;
use nzedb\PreDb;
use nzedb\ReleaseCleaning;
use nzedb\ReleaseImage;
use nzedb\Releases;
use nzedb\RequestIDLocal;
use nzedb\RequestIDWeb;
use nzedb\db\DB;

class ProcessReleases
{
	const COLLFC_DEFAULT  =  0; // Collection has default filecheck status
	const COLLFC_COMPCOLL =  1; // Collection is a complete collection
	const COLLFC_COMPPART =  2; // Collection is a complete collection and has all parts available
	const COLLFC_SIZED    =  3; // Collection has been calculated for total size
	const COLLFC_INSERTED =  4; // Collection has been inserted into releases
	const COLLFC_DELETE   =  5; // Collection is ready for deletion
	const COLLFC_TEMPCOMP = 15; // Collection is complete and being checked for complete parts
	const COLLFC_ZEROPART = 16; // Collection has a 00/0XX designator (temporary)

	const FILE_INCOMPLETE = 0; // We don't have all the parts yet for the file (binaries table partcheck column).
	const FILE_COMPLETE   = 1; // We have all the parts for the file (binaries table partcheck column).

	/**
	 * @var \nzedb\Groups
	 */
	public $groups;

	/**
	 * @var int
	 */
	public $collectionDelayTime;

	/**
	 * @var int
	 */
	public $crossPostTime;

	/**
	 * @var int
	 */
	public $releaseCreationLimit;

	/**
	 * @var int
	 */
	public $completion;

	/**
	 * @var int
	 */
	public $processRequestIDs;

	/**
	 * @var bool
	 */
	public $echoCLI;

	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var \nzedb\ConsoleTools
	 */
	public $consoleTools;

	/**
	 * @var \nzedb\NZB
	 */
	public $nzb;

	/**
	 * @var \nzedb\ReleaseCleaning
	 */
	public $releaseCleaning;

	/**
	 * @var \nzedb\Releases
	 */
	public $releases;

	/**
	 * @var \nzedb\ReleaseImage
	 */
	public $releaseImage;

	/**
	 * @var array $tables	List of table names to be using for method calls.
	 */
	protected $tables = [];

	/**
	 * @var string $fromNamesQuery
	 */
	protected $fromNamesQuery;

	/**
	 * @var int Time (hours) to wait before delete a stuck/broken collection.
	 */
	private $collectionTimeout;

	/**
	 * @param array $options Class instances / Echo to cli ?
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Echo'            => true,
			'ConsoleTools'    => null,
			'Groups'          => null,
			'NZB'             => null,
			'ReleaseCleaning' => null,
			'ReleaseImage'    => null,
			'Releases'        => null,
			'Settings'        => null,
		];
		$options += $defaults;

		$this->echoCLI = ($options['Echo'] && nZEDb_ECHOCLI);

		$this->pdo = ($options['Settings'] instanceof DB ? $options['Settings'] : new DB());
		$this->consoleTools = ($options['ConsoleTools'] instanceof ConsoleTools ? $options['ConsoleTools'] : new ConsoleTools(['ColorCLI' => $this->pdo->log]));
		$this->groups = ($options['Groups'] instanceof Groups ? $options['Groups'] : new Groups(['Settings' => $this->pdo]));
		$this->nzb = ($options['NZB'] instanceof NZB ? $options['NZB'] : new NZB($this->pdo));
		$this->releaseCleaning = ($options['ReleaseCleaning'] instanceof ReleaseCleaning ? $options['ReleaseCleaning'] : new ReleaseCleaning($this->pdo));
		$this->releases = ($options['Releases'] instanceof Releases ? $options['Releases'] : new Releases(['Settings' => $this->pdo, 'Groups' => $this->groups]));
		$this->releaseImage = ($options['ReleaseImage'] instanceof ReleaseImage ? $options['ReleaseImage'] : new ReleaseImage($this->pdo));

		$dummy = Settings::value('..delaytime');
		$this->collectionDelayTime = ($dummy === '' ? 2 : (int)$dummy);
		$dummy = Settings::value('..crossposttime');
		$this->crossPostTime = ($dummy === '' ? 2 : (int)$dummy);
		$dummy = Settings::value('..maxnzbsprocessed');
		$this->releaseCreationLimit = ($dummy === '' ? 1000 : (int)$dummy);
		$dummy = Settings::value('..releasecompletion');
		$this->completion = ($dummy === '' ? 0 : (int)$dummy);
		$this->processRequestIDs = (int)Settings::value('lookup_reqids');
		if ($this->completion > 100) {
			$this->completion = 100;
			echo $this->pdo->log->error(PHP_EOL . 'You have an invalid setting for completion. It cannot be higher than 100.');
		}
		$this->collectionTimeout = (int)Settings::value('indexer.processing.collection_timeout');
	}

	/**
	 * Main method for creating releases/NZB files from collections.
	 *
	 * @param int    $categorize
	 * @param int    $postProcess
	 * @param string $groupName (optional)
	 * @param \nzedb\NNTP   $nntp
	 * @param bool   $echooutput
	 *
	 * @return int
	 */
	public function processReleases($categorize, $postProcess, $groupName, &$nntp, $echooutput)
	{
		$this->echoCLI = ($echooutput && nZEDb_ECHOCLI);
		$groupID = '';

		if (!empty($groupName) && $groupName !== 'mgr') {
			//$groupInfo = $this->groups->getAllByName($groupName);
			//$groupID = $groupInfo['id'];
			$groupID = Group::getIDByName($groupName);
		}

		$processReleases = microtime(true);
		if ($this->echoCLI) {
			$this->pdo->log->doEcho($this->pdo->log->header('Starting release update process (' . date('Y-m-d H:i:s') . ')'), true);
		}

		if (!file_exists(Settings::value('..nzbpath'))) {
			if ($this->echoCLI) {
				$this->pdo->log->doEcho(
					$this->pdo->log->error('Bad or missing nzb directory - ' . Settings::value('..nzbpath')),
					true
				);
			}

			return 0;
		}

		$this->processIncompleteCollections($groupID);
		$this->processCollectionSizes($groupID);
		$this->deleteUnwantedCollections($groupID);

		$DIR = nZEDb_MISC;

		$totalReleasesAdded = 0;
		do {
			$releasesCount = $this->createReleases($groupID);
			$totalReleasesAdded += $releasesCount['added'];

			$nzbFilesAdded = $this->createNZBs($groupID);
			if ($this->processRequestIDs === 0) {
				$this->processRequestIDs($groupID, 5000, true);
			} else if ($this->processRequestIDs === 1) {
				$this->processRequestIDs($groupID, 5000, true);
				$this->processRequestIDs($groupID, 1000, false);
			} else if ($this->processRequestIDs === 2) {
				$requestIDTime = time();
				if ($this->echoCLI) {
					$this->pdo->log->doEcho($this->pdo->log->header("Process Releases -> Request ID Threaded lookup."));
				}
				passthru("${DIR}update/nix/multiprocessing/requestid.php");
				if ($this->echoCLI) {
					$this->pdo->log->doEcho(
						$this->pdo->log->primary(
							"\nReleases updated in " .
							$this->consoleTools->convertTime(time() - $requestIDTime)
						)
					);
				}
			}

			$this->categorizeReleases($categorize, $groupID);
			$this->postProcessReleases($postProcess, $nntp);
			$this->deleteCollections($groupID);

			// This loops as long as the number of releases or nzbs added was >= the limit (meaning there are more waiting to be created)
		} while (
			($releasesCount['added'] + $releasesCount['dupes'])	>= $this->releaseCreationLimit
				|| $nzbFilesAdded >= $this->releaseCreationLimit
		);

		// Only run if non-mgr as mgr is not specific to group
		if ($groupName !== 'mgr') {
			$this->deletedReleasesByGroup($groupID);
			$this->deleteReleases();
		}

		return $totalReleasesAdded;
	}

	/**
	 * Return all releases to other->misc category.
	 *
	 * @param string $where Optional "where" query parameter.
	 *
	 * @void
	 * @access public
	 */
	public function resetCategorize($where = '')
	{
		$this->pdo->queryExec(
			sprintf('UPDATE releases SET categories_id = %d, iscategorized = 0 %s', Category::OTHER_MISC, $where)
		);
	}

	/**
	 * Categorizes releases.
	 *
	 * @param string $type  name or searchname | Categorize using the search name or subject.
	 * @param string $where Optional "where" query parameter.
	 *
	 * @return int Quantity of categorized releases.
	 * @access public
	 */
	public function categorizeRelease($type, $where = '')
	{
		$cat = new Categorize(['Settings' => $this->pdo]);
		$categorized = $total = 0;
		$releases = $this->pdo->queryDirect(
			sprintf('
				SELECT id, %s, groups_id
				FROM releases %s',
				$type,
				$where
			)
		);
		if ($releases && $releases->rowCount()) {
			$total = $releases->rowCount();
			foreach ($releases as $release) {
				$catId = $cat->determineCategory($release['groups_id'], $release[$type]);
				$this->pdo->queryExec(
					sprintf('
						UPDATE releases
						SET categories_id = %d, iscategorized = 1
						WHERE id = %d',
						$catId,
						$release['id']
					)
				);
				$categorized++;
				if ($this->echoCLI) {
					$this->consoleTools->overWritePrimary(
						'Categorizing: ' . $this->consoleTools->percentString($categorized, $total)
					);
				}
			}
		}
		if ($this->echoCLI !== false && $categorized > 0) {
			echo PHP_EOL;
		}
		return $categorized;
	}

	public function processIncompleteCollections($groupID)
	{
		$startTime = time();
		$this->initiateTableNames($groupID);

		if ($this->echoCLI) {
			$this->pdo->log->doEcho($this->pdo->log->header("Process Releases -> Attempting to find complete collections."));
		}

		$where = (!empty($groupID) ? ' AND c.groups_id = ' . $groupID . ' ' : ' ');

		$this->processStuckCollections($where);
		$this->collectionFileCheckStage1($where);
		$this->collectionFileCheckStage2($where);
		$this->collectionFileCheckStage3($where);
		$this->collectionFileCheckStage4($where);
		$this->collectionFileCheckStage5($where);
		$this->collectionFileCheckStage6($where);

		if ($this->echoCLI) {
			$count = $this->pdo->queryOneRow(
				sprintf('
					SELECT COUNT(c.id) AS complete
					FROM %s c
					WHERE c.filecheck = %d %s',
					$this->tables['cname'],
					self::COLLFC_COMPPART,
					$where
				)
			);
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
					($count === false ? 0 : $count['complete']) . ' collections were found to be complete. Time: ' .
					$this->consoleTools->convertTime(time() - $startTime)
				), true
			);
		}
	}

	public function processCollectionSizes($groupID)
	{
		$startTime = time();
		$this->initiateTableNames($groupID);

		if ($this->echoCLI) {
			$this->pdo->log->doEcho($this->pdo->log->header("Process Releases -> Calculating collection sizes (in bytes)."));
		}
		// Get the total size in bytes of the collection for collections where filecheck = 2.
		$checked = $this->pdo->queryExec(
			sprintf('
				UPDATE %s c
				SET c.filesize =
				(
					SELECT COALESCE(SUM(b.partsize), 0)
					FROM %s b
					WHERE b.collections_id = c.id
				),
				c.filecheck = %d
				WHERE c.filecheck = %d
				AND c.filesize = 0 %s',
				$this->tables['cname'],
				$this->tables['bname'],
				self::COLLFC_SIZED,
				self::COLLFC_COMPPART,
				(!empty($groupID) ? ' AND c.groups_id = ' . $groupID : ' ')
			)
		);
		if ($checked !== false && $this->echoCLI) {
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
					$checked->rowCount() . " collections set to filecheck = 3(size calculated)"
				)
			);
			$this->pdo->log->doEcho($this->pdo->log->primary($this->consoleTools->convertTime(time() - $startTime)), true);
		}
	}

	public function deleteUnwantedCollections($groupID)
	{
		$startTime = time();
		$this->initiateTableNames($groupID);

		if ($this->echoCLI) {
			$this->pdo->log->doEcho(
				$this->pdo->log->header(
					"Process Releases -> Delete collections smaller/larger than minimum size/file count from group/site setting."
				)
			);
		}

		if ($groupID == '') {
			$groupIDs = Group::getActiveIDs()->data();
		} else {
			$groupIDs = [['id' => $groupID]];
		}

		$minSizeDeleted = $maxSizeDeleted = $minFilesDeleted = 0;

		$maxSizeSetting = Settings::value('..maxsizetoformrelease');
		$minSizeSetting = Settings::value('.release.minsizetoformrelease');
		$minFilesSetting = Settings::value('.release.minfilestoformrelease');

		foreach ($groupIDs as $groupID) {

			$groupMinSizeSetting = $groupMinFilesSetting = 0;

			$groupMinimums = Group::getAllByID($groupID['id']);
			if ($groupMinimums !== false) {
				if (!empty($groupMinimums['minsizetoformrelease']) && $groupMinimums['minsizetoformrelease'] > 0) {
					$groupMinSizeSetting = (int)$groupMinimums['minsizetoformrelease'];
				}
				if (!empty($groupMinimums['minfilestoformrelease']) && $groupMinimums['minfilestoformrelease'] > 0) {
					$groupMinFilesSetting = (int)$groupMinimums['minfilestoformrelease'];
				}
			}

			if ($this->pdo->queryOneRow(
					sprintf('
						SELECT SQL_NO_CACHE id
						FROM %s c
						WHERE c.filecheck = %d
						AND c.filesize > 0',
						$this->tables['cname'],
						self::COLLFC_SIZED
					)
				) !== false
			) {

				$deleteQuery = $this->pdo->queryExec(
					sprintf('
						DELETE c, b, p
						FROM %s c
						LEFT JOIN %s b ON c.id = b.collections_id
						LEFT JOIN %s p ON b.id = p.binaries_id
						WHERE c.filecheck = %d
						AND c.filesize > 0
						AND GREATEST(%d, %d) > 0
						AND c.filesize < GREATEST(%d, %d)',
						$this->tables['cname'],
						$this->tables['bname'],
						$this->tables['pname'],
						self::COLLFC_SIZED,
						$groupMinSizeSetting,
						$minSizeSetting,
						$groupMinSizeSetting,
						$minSizeSetting
					)
				);
				if ($deleteQuery !== false) {
					$minSizeDeleted += $deleteQuery->rowCount();
				}


				if ($maxSizeSetting > 0) {
					$deleteQuery = $this->pdo->queryExec(
						sprintf('
							DELETE c, b, p FROM %s c
							LEFT JOIN %s b ON c.id = b.collections_id
							LEFT JOIN %s p ON b.id = p.binaries_id
							WHERE c.filecheck = %d
							AND c.filesize > %d',
							$this->tables['cname'],
							$this->tables['bname'],
							$this->tables['pname'],
							self::COLLFC_SIZED,
							$maxSizeSetting
						)
					);
					if ($deleteQuery !== false) {
						$maxSizeDeleted += $deleteQuery->rowCount();
					}
				}

				$deleteQuery = $this->pdo->queryExec(
					sprintf('
						DELETE c, b, p FROM %s c
						LEFT JOIN %s b ON c.id = b.collections_id
						LEFT JOIN %s p ON b.id = p.binaries_id
						WHERE c.filecheck = %d
						AND GREATEST(%d, %d) > 0
						AND c.totalfiles < GREATEST(%d, %d)',
						$this->tables['cname'],
						$this->tables['bname'],
						$this->tables['pname'],
						self::COLLFC_SIZED,
						$groupMinFilesSetting,
						$minFilesSetting,
						$groupMinFilesSetting,
						$minFilesSetting
					)
				);
				if ($deleteQuery !== false) {
					$minFilesDeleted += $deleteQuery->rowCount();
				}
			}
		}

		if ($this->echoCLI) {
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
					'Deleted ' . ($minSizeDeleted + $maxSizeDeleted + $minFilesDeleted) . ' collections: ' . PHP_EOL .
					$minSizeDeleted . ' smaller than, ' .
					$maxSizeDeleted . ' bigger than, ' .
					$minFilesDeleted . ' with less files than site/group settings in: ' .
					$this->consoleTools->convertTime(time() - $startTime)
				), true
			);
		}
	}

	/**
	 * @param $groupID
	 *
	 * @void
	 */
	protected function initiateTableNames($groupID)
	{
		$this->tables = Tables::getTPGNamesFromId($groupID);
	}

	/**
	 * Form fromNamesQuery for creating NZBs
	 *
	 * @void
	 */
	protected function formFromNamesQuery()
	{
		$posters = MultigroupPosters::commaSeparatedList();
		$this->fromNamesQuery = sprintf("AND r.fromname NOT IN('%s')", $posters);
	}

	/**
	 * @param int|string $groupID (optional)
	 *
	 * @return array
	 * @throws \InvalidArgumentException if a new entry must be created but the 'name' is not set.
	 */
	public function createReleases($groupID)
	{
		$startTime = time();
		$this->initiateTableNames($groupID);

		$categorize = new Categorize(['Settings' => $this->pdo]);
		$returnCount = $duplicate = 0;

		if ($this->echoCLI) {
			$this->pdo->log->doEcho($this->pdo->log->header("Process Releases -> Create releases from complete collections."));
		}

		$this->pdo->ping(true);

		$groupsid = !empty($groupID) ? ' groups_id = ' . $groupID . ' AND ' : ' ';
		$collections = $this->pdo->queryDirect(
			sprintf('
				SELECT SQL_NO_CACHE c.*, g.name AS gname
				FROM %s c
				INNER JOIN groups g ON c.groups_id = g.id
				WHERE %s c.filecheck = %d
				AND c.filesize > 0
				LIMIT %d',
				$this->tables['cname'],
				$groupsid,
				self::COLLFC_SIZED,
				$this->releaseCreationLimit
			)
		);

		if ($this->echoCLI && $collections !== false) {
			echo $this->pdo->log->primary($collections->rowCount() . " Collections ready to be converted to releases.");
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
						AND size BETWEEN '%s' AND '%s'",
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

					if (\is_array($cleanedName)) {
						$properName = $cleanedName['properlynamed'];
						$preID = $cleanerName['predb'] ?? false;
						$isReqID = $cleanerName['requestid'] ?? false;
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
							'groups_id' => $collection['groups_id'],
							'guid' => $this->pdo->escapeString($this->releases->createGUID()),
							'postdate' => $this->pdo->escapeString($collection['date']),
							'fromname' => $fromName,
							'size' => $collection['filesize'],
							'categories_id' => $categorize->determineCategory($collection['groups_id'], $cleanedName),
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
								UPDATE %s
								SET filecheck = %d, releases_id = %d
								WHERE id = %d',
								$this->tables['cname'],
								self::COLLFC_INSERTED,
								$releaseID,
								$collection['id']
							)
						);

						if (preg_match_all('#(\S+):\S+#', $collection['xref'], $matches)) {
							foreach ($matches[1] as $grp) {
								//check if the group name is in a valid format
								$grpTmp = Group::isValidName($grp);
								if ($grpTmp !== false) {
									//check if the group already exists in database
									$xrefGrpID = Group::getIDByName($grpTmp);
									if ($xrefGrpID === '') {
										try {
											$newGroup = Group::create(
												[
													'name'        => $grpTmp,
													'description' => 'Added by Release processing',
												]
											);
										} catch (\InvalidArgumentException $e) {
											throw new \InvalidArgumentException($e->getMessage() .
												PHP_EOL .
												'Thrown in ProcessReleases.php');
										}
										$newGroup->save();
										$xrefGrpID = $newGroup->id;
									}

									$relGroupsChk = ReleasesGroups::find('first',
										[
											'conditions' =>
												[
													'releases_id' => $releaseID,
													'groups_id'   => $xrefGrpID,
												],
											'fields'     => ['releases_id'],
											'limit'      => 1,
										]
									);

									if ($relGroupsChk === null) {
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
							DELETE c, b, p
							FROM %s c
							INNER JOIN %s b ON c.id = b.collections_id
							STRAIGHT_JOIN %s p ON b.id = p.binaries_id
							WHERE c.collectionhash = %s',
							$this->tables['cname'],
							$this->tables['bname'],
							$this->tables['pname'],
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
	 * @param int|string $groupID (optional)
	 *
	 * @return int
	 * @access public
	 */
	public function createNZBs($groupID)
	{
		$startTime = time();
		$this->formFromnamesQuery();

		if ($this->echoCLI) {
			$this->pdo->log->doEcho($this->pdo->log->header("Process Releases -> Create the NZB, delete collections/binaries/parts."));
		}

		$groupsid = !empty($groupID) ? ' r.groups_id = ' . $groupID . ' AND ' : ' ';
		$releases = $this->pdo->queryDirect(
			sprintf("
				SELECT SQL_NO_CACHE
					CONCAT(COALESCE(cp.title,'') , CASE WHEN cp.title IS NULL THEN '' ELSE ' > ' END , c.title) AS title,
					r.name, r.id, r.guid
				FROM releases r
				INNER JOIN categories c ON r.categories_id = c.id
				INNER JOIN categories cp ON cp.id = c.parentid
				WHERE %s nzbstatus = 0 %s",
				$groupsid,
				$this->fromNamesQuery
			)
		);

		$nzbCount = 0;

		if ($releases && $releases->rowCount()) {
			$total = $releases->rowCount();
			// Init vars for writing the NZB's.
			$this->nzb->initiateForWrite($groupID);
			foreach ($releases as $release)
			{
				if ($this->nzb->writeNZBforReleaseId($release['id'], $release['guid'], $release['name'], $release['title']) === true) {
					$nzbCount++;
					if ($this->echoCLI) {
						echo $this->pdo->log->primaryOver("Creating NZBs and deleting Collections:\t" . $nzbCount . '/' . $total . "\r");
					}
				}
			}
		}

		$totalTime = (time() - $startTime);

		if ($this->echoCLI) {
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
					number_format($nzbCount) . ' NZBs created/Collections deleted in ' .
					$totalTime . ' seconds.' . PHP_EOL .
					'Total time: ' . $this->pdo->log->primary($this->consoleTools->convertTime($totalTime)) . PHP_EOL
				)
			);
		}

		return $nzbCount;
	}

	/**
	 * Process RequestID's.
	 *
	 * @param int|string  $groupID
	 * @param int  $limit
	 * @param bool $local
	 *
	 * @access public
	 * @void
	 */
	public function processRequestIDs($groupID = '', $limit = 5000, $local = true)
	{
		if ($local === false && Settings::value('..lookup_reqids') == 0) {
			return;
		}

		$startTime = time();
		if ($this->echoCLI) {
			$this->pdo->log->doEcho(
				$this->pdo->log->header(
					sprintf(
						"Process Releases -> Request ID %s lookup -- limit %s",
						($local === true ? 'local' : 'web'),
						$limit
					)
				)
			);
		}

		if ($local === true) {
			$foundRequestIDs = (
				new RequestIDLocal(
					[
						'Echo'         => $this->echoCLI,
						'ConsoleTools' => $this->consoleTools,
						'Groups'       => $this->groups,
						'Settings'     => $this->pdo,
					]
				)
			)->lookupRequestIDs(['GroupID' => $groupID, 'limit' => $limit, 'time' => 168]);
		} else {
			$foundRequestIDs = (
				new RequestIDWeb(
					[
						'Echo'         => $this->echoCLI,
						'ConsoleTools' => $this->consoleTools,
						'Groups'       => $this->groups,
						'Settings'     => $this->pdo,
					]
				)
			)->lookupRequestIDs(['GroupID' => $groupID, 'limit' => $limit, 'time' => 168]);
		}
		if ($this->echoCLI) {
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
					number_format($foundRequestIDs) .
					' releases updated in ' .
					$this->consoleTools->convertTime(time() - $startTime)
				), true
			);
		}
	}

	/**
	 * Categorize releases.
	 *
	 * @param int        $categorize
	 * @param int|string $groupID    (optional)
	 *
	 * @void
	 * @access public
	 */
	public function categorizeReleases($categorize, $groupID = '')
	{
		$startTime = time();
		if ($this->echoCLI) {
			echo $this->pdo->log->header("Process Releases -> Categorize releases.");
		}
		switch ((int)$categorize) {
			case 2:
				$type = 'searchname';
				break;
			case 1:
			default:

				$type = 'name';
				break;
		}
		$this->categorizeRelease(
			$type,
			(!empty($groupID)
				? 'WHERE categories_id = ' . Category::OTHER_MISC . ' AND iscategorized = 0 AND groups_id = ' . $groupID
				: 'WHERE categories_id = ' . Category::OTHER_MISC . ' AND iscategorized = 0')
		);

		if ($this->echoCLI) {
			$this->pdo->log->doEcho($this->pdo->log->primary($this->consoleTools->convertTime(time() - $startTime)), true);
		}
	}

	/**
	 * Post-process releases.
	 *
	 * @param int        $postProcess
	 * @param \nzedb\NNTP       $nntp
	 *
	 * @void
	 * @access public
	 */
	public function postProcessReleases($postProcess, &$nntp)
	{
		if ($postProcess == 1) {
			(new PostProcess(['Echo' => $this->echoCLI, 'Settings' => $this->pdo, 'Groups' => $this->groups]))->processAll($nntp);
		} else {
			if ($this->echoCLI) {
				$this->pdo->log->doEcho(
					$this->pdo->log->info(
						"\nPost-processing is not running inside the Process Releases class.\n" .
						"If you are using tmux or screen they might have their own scripts running Post-processing."
					)
				);
			}
		}
	}

	public function deleteCollections($groupID)
	{
		$startTime = time();
		$this->initiateTableNames($groupID);

		$deletedCount = 0;

		// CBP older than retention.
		if ($this->echoCLI) {
			echo (
				$this->pdo->log->header("Process Releases -> Delete finished collections." . PHP_EOL) .
				$this->pdo->log->primary(sprintf(
					'Deleting collections/binaries/parts older than %d hours.',
					Settings::value('..partretentionhours')
				))
			);
		}

		$deleted = 0;
		$deleteQuery = $this->pdo->queryExec(
			sprintf('
				DELETE c, b, p
				FROM %s c
				LEFT JOIN %s b ON c.id = b.collections_id
				LEFT JOIN %s p ON b.id = p.binaries_id
				WHERE (c.dateadded < NOW() - INTERVAL %d HOUR)',
				$this->tables['cname'],
				$this->tables['bname'],
				$this->tables['pname'],
				Settings::value('..partretentionhours')
			)
		);

		if ($deleteQuery !== false) {
			$deleted = $deleteQuery->rowCount();
			$deletedCount += $deleted;
		}

		$firstQuery = $fourthQuery = time();

		if ($this->echoCLI) {
			echo $this->pdo->log->primary(
				'Finished deleting ' . $deleted . ' old collections/binaries/parts in ' .
				($firstQuery - $startTime) . ' seconds.' . PHP_EOL
			);
		}

		// Cleanup orphaned collections, binaries and parts
		// this really shouldn't happen, but just incase - so we only run 1/200 of the time
		if (mt_rand(0, 200) <= 1) {
			// CBP collection orphaned with no binaries or parts.
			if ($this->echoCLI) {
				echo (
					$this->pdo->log->header("Process Releases -> Remove CBP orphans." . PHP_EOL) .
					$this->pdo->log->primary('Deleting orphaned collections.')
				);
			}

			$deleted = 0;
			$deleteQuery = $this->pdo->queryExec(
				sprintf('
					DELETE c, b, p
					FROM %s c
					LEFT JOIN %s b ON c.id = b.collections_id
					LEFT JOIN %s p ON b.id = p.binaries_id
					WHERE (b.id IS NULL OR p.binaries_id IS NULL)',
					$this->tables['cname'],
					$this->tables['bname'],
					$this->tables['pname']
				)
			);

			if ($deleteQuery !== false) {
				$deleted = $deleteQuery->rowCount();
				$deletedCount += $deleted;
			}

			$secondQuery = time();

			if ($this->echoCLI) {
				echo $this->pdo->log->primary(
					'Finished deleting ' . $deleted . ' orphaned collections in ' .
					($secondQuery - $firstQuery) . ' seconds.' . PHP_EOL
				);
			}

			// orphaned binaries - binaries with no parts or binaries with no collection
			// Don't delete currently inserting binaries by checking the max id.
			if ($this->echoCLI) {
				echo $this->pdo->log->primary('Deleting orphaned binaries/parts with no collection.');
			}

			$deleted = 0;
			$deleteQuery = $this->pdo->queryExec(
				sprintf(
					'DELETE b, p FROM %s b
					LEFT JOIN %s p ON b.id = p.binaries_id
					LEFT JOIN %s c ON b.collections_id = c.id
					WHERE (p.binaries_id IS NULL OR c.id IS NULL)
					AND b.id < %d',
					$this->tables['bname'],
					$this->tables['pname'],
					$this->tables['cname'],
					$this->maxQueryFormulator($this->tables['bname'], 20000)
				)
			);

			if ($deleteQuery !== false) {
				$deleted = $deleteQuery->rowCount();
				$deletedCount += $deleted;
			}

			$thirdQuery = time();

			if ($this->echoCLI) {
				echo $this->pdo->log->primary(
					'Finished deleting ' . $deleted . ' binaries with no collections or parts in ' .
					($thirdQuery - $secondQuery) . ' seconds.'
				);
			}

			// orphaned parts - parts with no binary
			// Don't delete currently inserting parts by checking the max id.
			if ($this->echoCLI) {
				echo $this->pdo->log->primary('Deleting orphaned parts with no binaries.');
			}
			$deleted = 0;
			$deleteQuery = $this->pdo->queryExec(
				sprintf('
					DELETE p
					FROM %s p
					LEFT JOIN %s b ON p.binaries_id = b.id
					WHERE b.id IS NULL
					AND p.binaries_id < %d',
					$this->tables['pname'],
					$this->tables['bname'],
					$this->maxQueryFormulator($this->tables['bname'], 20000)
				)
			);
			if ($deleteQuery !== false) {
				$deleted = $deleteQuery->rowCount();
				$deletedCount += $deleted;
			}

			$fourthQuery = time();

			if ($this->echoCLI) {
					echo $this->pdo->log->primary(
						'Finished deleting ' . $deleted . ' parts with no binaries in ' .
						($fourthQuery - $thirdQuery) . ' seconds.' . PHP_EOL
					);
			}
		} // done cleaning up Binaries/Parts orphans

		if ($this->echoCLI) {
			echo $this->pdo->log->primary(
				'Deleting collections that were missed after NZB creation.'
			);
		}

		$deleted = 0;
		// Collections that were missing on NZB creation.
		$collections = $this->pdo->queryDirect(
			sprintf('
				SELECT SQL_NO_CACHE c.id
				FROM %s c
				INNER JOIN releases r ON r.id = c.releases_id
				WHERE r.nzbstatus = 1',
				$this->tables['cname']
			)
		);

		if ($collections instanceof \Traversable) {
			foreach ($collections as $collection) {
				$deleted++;
				$this->pdo->queryExec(
					sprintf('
						DELETE c, b, p
						FROM %s c
						LEFT JOIN %s b ON(c.id=b.collections_id)
						LEFT JOIN %s p ON(b.id=p.binaries_id)
						WHERE c.id = %d',
						$this->tables['cname'],
						$this->tables['bname'],
						$this->tables['pname'],
						$collection['id']
					)
				);
			}
			$deletedCount += $deleted;
		}

		if ($this->echoCLI) {
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
					'Finished deleting ' . $deleted . ' collections missed after NZB creation in ' .
					(time() - $fourthQuery) . ' seconds.' . PHP_EOL .
					'Removed ' .
					number_format($deletedCount) .
					' parts/binaries/collection rows in ' .
					$this->consoleTools->convertTime(($fourthQuery - $startTime)) . PHP_EOL
				)
			);
		}
	}

	/**
	 * Delete unwanted releases based on admin settings.
	 * This deletes releases based on group.
	 *
	 * @param int|string $groupID (optional)
	 *
	 * @void
	 * @access public
	 */
	public function deletedReleasesByGroup($groupID = '')
	{
		$startTime = time();
		$minSizeDeleted = $maxSizeDeleted = $minFilesDeleted = 0;

		if ($this->echoCLI) {
			echo $this->pdo->log->header("Process Releases -> Delete releases smaller/larger than minimum size/file count from group/site setting.");
		}

		if ($groupID == '') {
			$groupIDs = Group::getActiveIDs()->data();
		} else {
			$groupIDs = [['id' => $groupID]];
		}

		$maxSizeSetting = Settings::value('..maxsizetoformrelease');
		$minSizeSetting = Settings::value('.release.minsizetoformrelease');
		$minFilesSetting = Settings::value('.release.minfilestoformrelease');

		foreach ($groupIDs as $groupID) {
			$releases = $this->pdo->queryDirect(
				sprintf("
					SELECT SQL_NO_CACHE r.guid, r.id
					FROM releases r
					INNER JOIN groups g ON g.id = r.groups_id
					WHERE r.groups_id = %d
					AND greatest(IFNULL(g.minsizetoformrelease, 0), %d) > 0
					AND r.size < greatest(IFNULL(g.minsizetoformrelease, 0), %d)",
					$groupID['id'],
					$minSizeSetting,
					$minSizeSetting
				)
			);
			if ($releases instanceof \Traversable) {
				foreach ($releases as $release) {
					$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
					$minSizeDeleted++;
				}
			}

			if ($maxSizeSetting > 0) {
				$releases = $this->pdo->queryDirect(
					sprintf('
						SELECT SQL_NO_CACHE id, guid
						FROM releases
						WHERE groups_id = %d
						AND size > %d',
						$groupID['id'],
						$maxSizeSetting
					)
				);
				if ($releases instanceof \Traversable) {
					foreach ($releases as $release) {
						$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
						$maxSizeDeleted++;
					}
				}
			}

			$releases = $this->pdo->queryDirect(
				sprintf("
					SELECT SQL_NO_CACHE r.id, r.guid
					FROM releases r
					INNER JOIN groups g ON g.id = r.groups_id
					WHERE r.groups_id = %d
					AND greatest(IFNULL(g.minfilestoformrelease, 0), %d) > 0
					AND r.totalpart < greatest(IFNULL(g.minfilestoformrelease, 0), %d)",
					$groupID['id'],
					$minFilesSetting,
					$minFilesSetting
				)
			);
			if ($releases instanceof \Traversable) {
				foreach ($releases as $release) {
					$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
					$minFilesDeleted++;
				}
			}
		}

		if ($this->echoCLI) {
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
					'Deleted ' . ($minSizeDeleted + $maxSizeDeleted + $minFilesDeleted) .
					' releases: ' . PHP_EOL .
					$minSizeDeleted . ' smaller than, ' . $maxSizeDeleted . ' bigger than, ' . $minFilesDeleted .
					' with less files than site/groups setting in: ' .
					$this->consoleTools->convertTime(time() - $startTime)
				), true
			);
		}
	}

	/**
	 * Delete releases using admin settings.
	 * This deletes releases, regardless of group.
	 *
	 * @void
	 * @access public
	 */
	public function deleteReleases()
	{
		$startTime = time();
		$category = new Category(['Settings' => $this->pdo]);
		$genres = new Genres(['Settings' => $this->pdo]);
		$passwordDeleted = $duplicateDeleted = $retentionDeleted = $completionDeleted = $disabledCategoryDeleted = 0;
		$disabledGenreDeleted = $miscRetentionDeleted = $miscHashedDeleted = $categoryMinSizeDeleted = 0;

		// Delete old releases and finished collections.
		if ($this->echoCLI) {
			$this->pdo->log->doEcho($this->pdo->log->header("Process Releases -> Delete old releases and passworded releases."));
		}

		// Releases past retention.
		if (Settings::value('..releaseretentiondays') != 0) {
			$releases = $this->pdo->queryDirect(
				sprintf(
					'SELECT SQL_NO_CACHE id, guid FROM releases WHERE postdate < (NOW() - INTERVAL %d DAY)',
					Settings::value('..releaseretentiondays')
				)
			);
			if ($releases instanceof \Traversable) {
				foreach ($releases as $release) {
					$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
					$retentionDeleted++;
				}
			}
		}

		// Passworded releases.
		if (Settings::value('..deletepasswordedrelease') == 1) {
			$releases = $this->pdo->queryDirect(
				sprintf(
					'SELECT SQL_NO_CACHE id, guid FROM releases WHERE passwordstatus = %d',
					Releases::PASSWD_RAR
				)
			);
			if ($releases instanceof \Traversable) {
				foreach ($releases as $release) {
					$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
					$passwordDeleted++;
				}
			}
		}

		// Possibly passworded releases.
		if (Settings::value('..deletepossiblerelease') == 1) {
			$releases = $this->pdo->queryDirect(
				sprintf(
					'SELECT SQL_NO_CACHE id, guid FROM releases WHERE passwordstatus = %d',
					Releases::PASSWD_POTENTIAL
				)
			);
			if ($releases instanceof \Traversable) {
				foreach ($releases as $release) {
					$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
					$passwordDeleted++;
				}
			}
		}

		if ($this->crossPostTime != 0) {
			// Crossposted releases.
			$releases = $this->pdo->queryDirect(
				sprintf(
					'SELECT SQL_NO_CACHE id, guid FROM releases WHERE adddate > (NOW() - INTERVAL %d HOUR) GROUP BY name HAVING COUNT(name) > 1',
					$this->crossPostTime
				)
			);
			if ($releases instanceof \Traversable) {
				foreach ($releases as $release) {
					$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
					$duplicateDeleted++;
				}
			}
		}

		if ($this->completion > 0) {
			$releases = $this->pdo->queryDirect(
				sprintf('SELECT SQL_NO_CACHE id, guid FROM releases WHERE completion < %d AND completion > 0', $this->completion)
			);
			if ($releases instanceof \Traversable) {
				foreach ($releases as $release) {
					$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
					$completionDeleted++;
				}
			}
		}

		// Disabled categories.
		$disabledCategories = $category->getDisabledIDs();
		if (count($disabledCategories) > 0) {
			foreach ($disabledCategories as $disabledCategory) {
				$releases = $this->pdo->queryDirect(
					sprintf('SELECT SQL_NO_CACHE id, guid FROM releases WHERE categories_id = %d', $disabledCategory['id'])
				);
				if ($releases instanceof \Traversable) {
					foreach ($releases as $release) {
						$disabledCategoryDeleted++;
						$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
					}
				}
			}
		}

		// Delete smaller than category minimum sizes.
		$categories = $this->pdo->queryDirect('
			SELECT SQL_NO_CACHE c.id AS id,
			CASE WHEN c.minsize = 0 THEN cp.minsize ELSE c.minsize END AS minsize
			FROM categories c
			INNER JOIN categories cp ON cp.id = c.parentid
			WHERE c.parentid IS NOT NULL'
		);

		if ($categories instanceof \Traversable) {
			foreach ($categories as $category) {
				if ($category['minsize'] > 0) {
					$releases = $this->pdo->queryDirect(
						sprintf('
							SELECT SQL_NO_CACHE r.id, r.guid
							FROM releases r
							WHERE r.categories_id = %d
							AND r.size < %d
							LIMIT 1000',
							$category['id'],
							$category['minsize']
						)
					);
					if ($releases instanceof \Traversable) {
						foreach ($releases as $release) {
							$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
							$categoryMinSizeDeleted++;
						}
					}
				}
			}
		}

		// Disabled music genres.
		$genrelist = $genres->getDisabledIDs();
		if (count($genrelist) > 0) {
			foreach ($genrelist as $genre) {
				$releases = $this->pdo->queryDirect(
					sprintf('
						SELECT SQL_NO_CACHE id, guid
						FROM releases
						INNER JOIN
						(
							SELECT id AS mid
							FROM musicinfo
							WHERE musicinfo.genre_id = %d
						) mi ON musicinfo_id = mid',
						$genre['id']
					)
				);
				if ($releases instanceof \Traversable) {
					foreach ($releases as $release) {
						$disabledGenreDeleted++;
						$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
					}
				}
			}
		}

		// Misc other.
		if (Settings::value('..miscotherretentionhours') > 0) {
			$releases = $this->pdo->queryDirect(
				sprintf('
					SELECT SQL_NO_CACHE id, guid
					FROM releases
					WHERE categories_id = %d
					AND adddate <= NOW() - INTERVAL %d HOUR',
					Category::OTHER_MISC,
					Settings::value('..miscotherretentionhours')
				)
			);
			if ($releases instanceof \Traversable) {
				foreach ($releases as $release) {
					$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
					$miscRetentionDeleted++;
				}
			}
		}

		// Misc hashed.
		if (Settings::value('..mischashedretentionhours') > 0) {
			$releases = $this->pdo->queryDirect(
				sprintf('
					SELECT SQL_NO_CACHE id, guid
					FROM releases
					WHERE categories_id = %d
					AND adddate <= NOW() - INTERVAL %d HOUR',
					Category::OTHER_HASHED,
					Settings::value('..mischashedretentionhours')
				)
			);
			if ($releases instanceof \Traversable) {
				foreach ($releases as $release) {
					$this->releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $this->nzb, $this->releaseImage);
					$miscHashedDeleted++;
				}
			}
		}

		if ($this->echoCLI) {
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
					'Removed releases: ' .
					number_format($retentionDeleted) .
					' past retention, ' .
					number_format($passwordDeleted) .
					' passworded, ' .
					number_format($duplicateDeleted) .
					' crossposted, ' .
					number_format($disabledCategoryDeleted) .
					' from disabled categories, ' .
					number_format($categoryMinSizeDeleted) .
					' smaller than category settings, ' .
					number_format($disabledGenreDeleted) .
					' from disabled music genres, ' .
					number_format($miscRetentionDeleted) .
					' from misc->other' .
					number_format($miscHashedDeleted) .
					' from misc->hashed' .
					($this->completion > 0
						? ', ' . number_format($completionDeleted) . ' under ' . $this->completion . '% completion.'
						: '.'
					)
				)
			);

			$totalDeleted = (
				$retentionDeleted + $passwordDeleted + $duplicateDeleted + $disabledCategoryDeleted +
				$disabledGenreDeleted + $miscRetentionDeleted + $miscHashedDeleted + $completionDeleted +
				$categoryMinSizeDeleted
			);
			if ($totalDeleted > 0) {
				$this->pdo->log->doEcho(
					$this->pdo->log->primary(
						"Removed " . number_format($totalDeleted) . ' releases in ' .
						$this->consoleTools->convertTime(time() - $startTime)
					)
				);
			}
		}
	}

	/**
	 * Formulate part of a query to prevent deletion of currently inserting parts / binaries / collections.
	 *
	 * @param string $groupName
	 * @param int    $difference
	 *
	 * @return string
	 * @access private
	 */
	private function maxQueryFormulator($groupName, $difference)
	{
		$maxID = $this->pdo->queryOneRow(
			sprintf('
				SELECT IFNULL(MAX(id),0) AS max
				FROM %s',
				$groupName
			)
		);
		return empty($maxID['max']) || $maxID['max'] < $difference ? 0 : $maxID['max'] - $difference;
	}

	/**
	 * Look if we have all the files in a collection (which have the file count in the subject).
	 * Set file check to complete.
	 * This means the the binary table has the same count as the file count in the subject, but
	 * the collection might not be complete yet since we might not have all the articles in the parts table.
	 *
	 * @param string $where
	 *
	 * @void
	 * @access private
	 */
	private function collectionFileCheckStage1(&$where)
	{

		$this->pdo->queryExec(
			sprintf('
				UPDATE %s c
				INNER JOIN
				(
					SELECT c.id
					FROM %s c
					INNER JOIN %s b ON b.collections_id = c.id
					WHERE c.totalfiles > 0
					AND c.filecheck = %d %s
					GROUP BY b.collections_id, c.totalfiles, c.id
					HAVING COUNT(b.id) IN (c.totalfiles, c.totalfiles + 1)
				) r ON c.id = r.id
				SET filecheck = %d',
				$this->tables['cname'],
				$this->tables['cname'],
				$this->tables['bname'],
				self::COLLFC_DEFAULT,
				$where,
				self::COLLFC_COMPCOLL
			)
		);
	}

	/**
	 * The first query sets filecheck to COLLFC_ZEROPART if there's a file that starts with 0 (ex. [00/100]).
	 * The second query sets filecheck to COLLFC_TEMPCOMP on everything left over, so anything that starts with 1 (ex. [01/100]).
	 *
	 * This is done because some collections start at 0 and some at 1, so if you were to assume the collection is complete
	 * at 0 then you would never get a complete collection if it starts with 1 and if it starts, you can end up creating
	 * a incomplete collection, since you assumed it was complete.
	 *
	 * @param string $where
	 *
	 * @void
	 * @access private
	 */
	private function collectionFileCheckStage2(&$where)
	{
		$this->pdo->queryExec(
			sprintf('
				UPDATE %s c
				INNER JOIN
				(
					SELECT c.id
					FROM %s c
					INNER JOIN %s b ON b.collections_id = c.id
					WHERE b.filenumber = 0
					AND c.totalfiles > 0
					AND c.filecheck = %d %s
					GROUP BY c.id
				) r ON c.id = r.id
				SET c.filecheck = %d',
				$this->tables['cname'],
				$this->tables['cname'],
				$this->tables['bname'],
				self::COLLFC_COMPCOLL,
				$where,
				self::COLLFC_ZEROPART
			)
		);
		$this->pdo->queryExec(
			sprintf('
				UPDATE %s c
				SET filecheck = %d
				WHERE filecheck = %d %s',
				$this->tables['cname'],
				self::COLLFC_TEMPCOMP,
				self::COLLFC_COMPCOLL,
				$where
			)
		);
	}

	/**
	 * Check if the files (binaries table) in a complete collection has all the parts.
	 * If we have all the parts, set binaries table partcheck to FILE_COMPLETE.
	 *
	 * @param string $where
	 *
	 * @void
	 * @access private
	 */
	private function collectionFileCheckStage3($where)
	{

		$this->pdo->queryExec(
			sprintf('
				UPDATE %s b
				INNER JOIN
				(
					SELECT b.id
					FROM %s b
					INNER JOIN %s c ON c.id = b.collections_id
					WHERE c.filecheck = %d
					AND b.partcheck = %d %s
					AND b.currentparts = b.totalparts
					GROUP BY b.id, b.totalparts
				) r ON b.id = r.id
				SET b.partcheck = %d',
				$this->tables['bname'],
				$this->tables['bname'],
				$this->tables['cname'],
				self::COLLFC_TEMPCOMP,
				self::FILE_INCOMPLETE,
				$where,
				self::FILE_COMPLETE
			)
		);
		$this->pdo->queryExec(
			sprintf('
				UPDATE %s b
				INNER JOIN
				(
					SELECT b.id
					FROM %s b
					INNER JOIN %s c ON c.id = b.collections_id
					WHERE c.filecheck = %d
					AND b.partcheck = %d %s
					AND b.currentparts >= (b.totalparts + 1)
					GROUP BY b.id, b.totalparts
				) r ON b.id = r.id
				SET b.partcheck = %d',
				$this->tables['bname'],
				$this->tables['bname'],
				$this->tables['cname'],
				self::COLLFC_ZEROPART,
				self::FILE_INCOMPLETE,
				$where,
				self::FILE_COMPLETE
			)
		);
	}

	/**
	 * Check if all files (binaries table) for a collection are complete (if they all have the "parts").
	 * Set collections filecheck column to COLLFC_COMPPART.
	 * This means the collection is complete.
	 *
	 * @param string $where
	 *
	 * @void
	 * @access private
	 */
	private function collectionFileCheckStage4(&$where)
	{

		$this->pdo->queryExec(
			sprintf('
				UPDATE %s c INNER JOIN
					(SELECT c.id FROM %s c
					INNER JOIN %s b ON c.id = b.collections_id
					WHERE b.partcheck = 1 AND c.filecheck IN (%d, %d) %s
					GROUP BY b.collections_id, c.totalfiles, c.id HAVING COUNT(b.id) >= c.totalfiles)
				r ON c.id = r.id SET filecheck = %d',
				$this->tables['cname'],
				$this->tables['cname'],
				$this->tables['bname'],
				self::COLLFC_TEMPCOMP,
				self::COLLFC_ZEROPART,
				$where,
				self::COLLFC_COMPPART
			)
		);
	}

	/**
	 * If not all files (binaries table) had their parts on the previous stage,
	 * reset the collection filecheck column to COLLFC_COMPCOLL so we reprocess them next time.
	 *
	 * @param string $where
	 *
	 * @void
	 * @access private
	 */
	private function collectionFileCheckStage5(&$where)
	{

		$this->pdo->queryExec(
			sprintf('
				UPDATE %s c
				SET filecheck = %d
				WHERE filecheck IN (%d, %d) %s',
				$this->tables['cname'],
				self::COLLFC_COMPCOLL,
				self::COLLFC_TEMPCOMP,
				self::COLLFC_ZEROPART,
				$where
			)
		);
	}

	/**
	 * If a collection did not have the file count (ie: [00/12]) or the collection is incomplete after
	 * $this->collectionDelayTime hours, set the collection to complete to create it into a release/nzb.
	 *
	 * @param string $where
	 *
	 * @void
	 * @access private
	 */
	private function collectionFileCheckStage6(&$where)
	{

		$this->pdo->queryExec(
			sprintf("
				UPDATE %s c SET filecheck = %d, totalfiles = (SELECT COUNT(b.id) FROM %s b WHERE b.collections_id = c.id)
				WHERE c.dateadded < NOW() - INTERVAL '%d' HOUR
				AND c.filecheck IN (%d, %d, 10) %s",
				$this->tables['cname'],
				self::COLLFC_COMPPART,
				$this->tables['bname'],
				$this->collectionDelayTime,
				self::COLLFC_DEFAULT,
				self::COLLFC_COMPCOLL,
				$where
			)
		);
	}

	/**
	 * If a collection has been stuck for $this->collectionTimeout hours, delete it, it's bad.
	 *
	 * @param string $where
	 *
	 * @void
	 * @access private
	 */
	private function processStuckCollections($where)
	{
		$lastRun = Settings::value('indexer.processing.last_run_time');

		$obj = $this->pdo->queryExec(
			sprintf("
                DELETE c, b, p FROM %s c
                LEFT JOIN %s b ON (c.id=b.collections_id)
                LEFT JOIN %s p ON (b.id=p.binaries_id)
                WHERE
                    c.added <
                    DATE_SUB({$this->pdo->escapeString($lastRun)}, INTERVAL %d HOUR)
                %s",
				$this->tables['cname'],
				$this->tables['bname'],
				$this->tables['pname'],
				$this->collectionTimeout,
				$where
			)
		);
		if ($this->echoCLI && is_object($obj) && $obj->rowCount()) {
			$this->pdo->log->doEcho(
				$this->pdo->log->primary('Deleted ' . $obj->rowCount() . ' broken/stuck collections.')
			);
		}
	}
}
