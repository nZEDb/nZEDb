<?php

namespace nzedb;

use nzedb\db\Settings;


class DnzbFailures
{
	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var array $options Class instances.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
	}

	/**
	 * @param string $guid
	 */
	public function getFailedCount($guid)
	{
		$result = $this->pdo->query(sprintf('SELECT COUNT(userid) AS num FROM dnzb_failures WHERE guid = %s', $this->pdo->escapeString($guid)));
		return $result[0]['num'];
	}

	/**
	 * Retrieve alternate release with same or similar searchname
	 *
	 * @param string $guid
	 * @param string $searchname
	 * @param string $userid
	 * @return string
	 */
	public function getAlternate($guid, $searchname, $userid)
	{
		$this->pdo->queryInsert(sprintf("INSERT IGNORE INTO dnzb_failures (userid, guid) VALUES (%d, %s)",
				$userid,
				$this->pdo->escapeString($guid)
			)
		);
		$alternate = $this->pdo->queryOneRow(sprintf('SELECT * FROM releases r
			WHERE r.searchname %s
			AND r.guid NOT IN (SELECT guid FROM dnzb_failures WHERE userid = %d)',
				$this->pdo->likeString($searchname),
				$userid
			)
		);
		return $alternate;
	}
}