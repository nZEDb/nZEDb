<?php

namespace nzedb;

use nzedb\db\Settings;
use nzedb\ReleaseComments;


class DnzbFailures
{
	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var \nzedb\ReleaseComments
	 */
	public $rc;

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
		$this->rc = new ReleaseComments(['Settings' => $this->pdo]);
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
	 * Get a count of failed releases for pager. used in admin manage failed releases list
	 */
	public function getCount()
	{
		$res = $this->pdo->queryOneRow("SELECT count(id) AS num FROM dnzb_failures");
		return $res["num"];
	}

	/**
	 * Get a range of releases. used in admin manage list
	 *
	 * @param $start
	 * @param $num
	 *
	 * @return array
	 */
	public function getFailedRange($start, $num)
	{
		if ($start === false) {
			$limit = '';
		} else {
			$limit = ' LIMIT ' . $start . ',' . $num;
		}

		return $this->pdo->query("SELECT r.*, concat(cp.title, ' > ', c.title) AS category_name
 									FROM releases r
 									RIGHT JOIN dnzb_failures df ON df.guid = r.guid
 									LEFT OUTER JOIN category c ON c.id = r.categoryid
 									LEFT OUTER JOIN category cp ON cp.id = c.parentid
 									ORDER BY postdate DESC" . $limit
		);
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

		$rel = $this->pdo->queryOneRow(sprintf('SELECT id FROM releases WHERE guid = %s', $this->pdo->escapeString($guid)));
		$this->postComment($rel['id'], $userid);

		$alternate = $this->pdo->queryOneRow(sprintf('SELECT * FROM releases r
			WHERE r.searchname %s
			AND r.guid NOT IN (SELECT guid FROM dnzb_failures WHERE userid = %d)',
				$this->pdo->likeString($searchname),
				$userid
			)
		);
		return $alternate;
	}

	/**
	 * @param $relid
	 * @param $uid
	 */
	public function postComment($relid, $uid)
	{
		$text = 'This release has failed to download properly. It might fail for other users too.
		This comment is automatically generated.';
		$dbl = $this->pdo->queryOneRow(sprintf('SELECT text FROM release_comments WHERE releaseid = %d AND userid = %d', $relid, $uid));
		if ($dbl['text'] != $text) {
			$this->rc->addComment($relid, $text, $uid, '');
		}
	}
}