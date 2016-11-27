<?php
namespace nzedb;

use nzedb\db\DB;

/**
 * Class UserSeries
 *
 * Sets and Gets data from and to the DB "userseries" table and the "my shows" web-page.
 */
class UserSeries
{
	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null,
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof DB ? $options['Settings'] : new DB());
	}

	/**
	 * When a user wants to add a show to "my shows" insert it into the user series table.
	 *
	 * @param int   $uID    ID of user.
	 * @param int   $videoId Video ID of tv show.
	 * @param array $catID  List of category ID's
	 *
	 * @return bool|int
	 */
	public function addShow($uID, $videoId, $catID = [])
	{
		return $this->pdo->queryInsert(
			sprintf(
				"INSERT INTO user_series (user_id, videos_id, categories, createddate) VALUES (%d, %d, %s, NOW())",
				$uID,
				$videoId,
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
				SELECT us.*, v.title
				FROM user_series us
				INNER JOIN videos v ON v.id = us.videos_id
				WHERE user_id = %d
				ORDER BY v.title ASC",
				$uID
			)
		);
	}

	/**
	 * Delete a tv show from the user's "my shows".
	 *
	 * @param int $uID    ID of user.
	 * @param int $videoId ID of tv show.
	 */
	public function delShow($uID, $videoId)
	{
		$this->pdo->queryExec(
			sprintf(
				"DELETE FROM user_series WHERE user_id = %d AND videos_id = %d",
				$uID,
				$videoId
			)
		);
	}

	/**
	 * Get tv show information for a user.
	 *
	 * @param int $uID    ID of the user.
	 * @param int $videoId ID of the TV show.
	 *
	 * @return array|bool
	 */
	public function getShow($uID, $videoId)
	{
		return $this->pdo->queryOneRow(
			sprintf("
				SELECT us.*, v.title
				FROM user_series us
				LEFT OUTER JOIN videos v ON v.id = us.videos_id
				WHERE us.user_id = %d
				AND us.videos_id = %d",
				$uID,
				$videoId
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
				"DELETE FROM user_series WHERE user_id = %d",
				$uID
			)
		);
	}

	/**
	 * Delete TV shows from all user's "my shows" that match a TV id.
	 *
	 * @param int $videoId The ID of the TV show.
	 */
	public function delShowForSeries($videoId)
	{
		$this->pdo->queryExec(
			sprintf(
				"DELETE FROM user_series WHERE videos_id = %d",
				$videoId
			)
		);
	}

	/**
	 * Update a TV show category ID for a user's "my show" TV show.
	 *
	 * @param int   $uID    ID of the user.
	 * @param int $videoId ID of the TV show.
	 * @param array $catID  List of category ID's.
	 */
	public function updateShow($uID, $videoId, $catID = [])
	{
		$this->pdo->queryExec(
			sprintf(
				"UPDATE user_series SET categories = %s WHERE user_id = %d AND videos_id = %d",
				(!empty($catID) ? $this->pdo->escapeString(implode('|', $catID)) : "NULL"),
				$uID,
				$videoId
			)
		);
	}
}
