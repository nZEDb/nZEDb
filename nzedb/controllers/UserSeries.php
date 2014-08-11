<?php

use nzedb\db\Settings;

/**
 * Class UserSeries
 *
 * Sets and Gets data from and to the DB "userseries" table and the "my shows" web-page.
 */
class UserSeries
{
	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = array())
	{
		$defaults = [
			'Settings' => null,
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
	}

	/**
	 * When a user wants to add a show to "my shows" insert it into the user series table.
	 *
	 * @param int   $uID    ID of user.
	 * @param int   $rageID Rage ID of tv show.
	 * @param array $catID  List of category ID's
	 *
	 * @return bool|int
	 */
	public function addShow($uID, $rageID, $catID = array())
	{
		return $this->pdo->queryInsert(
			sprintf(
				"INSERT INTO userseries (userid, rageid, categoryid, createddate) VALUES (%d, %d, %s, NOW())",
				$uID,
				$rageID,
				(!empty($catID) ? $this->pdo->escapeString(implode('|', $catID)) : "NULL")
			)
		);
	}

	/**
	 * Get all the user's "my shows".
	 *
	 * @param int $uID ID of user.
	 *
	 * @return array
	 */
	public function getShows($uID)
	{
		return $this->pdo->query(
			sprintf("
				SELECT userseries.*, tvrage.releasetitle
				FROM userseries
				INNER JOIN tvrage ON tvrage.rageid = userseries.rageid
				WHERE userid = %d
				ORDER BY tvrage.releasetitle ASC",
				$uID
			)
		);
	}

	/**
	 * Delete a tv show from the user's "my shows".
	 *
	 * @param int $uID    ID of user.
	 * @param int $rageID ID of tv show.
	 */
	public function delShow($uID, $rageID)
	{
		$this->pdo->queryExec(
			sprintf(
				"DELETE FROM userseries WHERE userid = %d AND rageid = %d",
				$uID,
				$rageID
			)
		);
	}

	/**
	 * Get tv show information for a user.
	 *
	 * @param int $uID    ID of the user.
	 * @param int $rageID ID of the TV show.
	 *
	 * @return array|bool
	 */
	public function getShow($uID, $rageID)
	{
		return $this->pdo->queryOneRow(
			sprintf("
				SELECT userseries.*, tvr.releasetitle
				FROM userseries
				LEFT OUTER JOIN tvrage tvr ON tvr.rageid = userseries.rageid
				WHERE userseries.userid = %d
				AND userseries.rageid = %d",
				$uID,
				$rageID
			)
		);
	}

	/**
	 * Delete all shows from the user's "my shows".
	 *
	 * @param int $uID ID of the user.
	 */
	public function delShowForUser($uID)
	{
		$this->pdo->queryExec(
			sprintf(
				"DELETE FROM userseries WHERE userid = %d",
				$uID
			)
		);
	}

	/**
	 * Delete TV shows from all user's "my shows" that match a TV id.
	 *
	 * @param int $rageID The ID of the TV show.
	 */
	public function delShowForSeries($rageID)
	{
		$this->pdo->queryExec(
			sprintf(
				"DELETE FROM userseries WHERE rageid = %d",
				$rageID
			)
		);
	}

	/**
	 * Update a TV show category ID for a user's "my show" TV show.
	 * @param int   $uID    ID of the user.
	 * @param int   $rageID ID of the TV show.
	 * @param array $catID  List of category ID's.
	 */
	public function updateShow($uID, $rageID, $catID = array())
	{
		$this->pdo->queryExec(
			sprintf(
				"UPDATE userseries SET categoryid = %s WHERE userid = %d AND rageid = %d",
				(!empty($catID) ? $this->pdo->escapeString(implode('|', $catID)) : "NULL"),
				$uID,
				$rageID
			)
		);
	}
}
