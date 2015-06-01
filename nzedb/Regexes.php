<?php
namespace nzedb;

use nzedb\db\Settings;

class Regexes
{
	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var string Name of the current table we are working on.
	 */
	public $tableName;

	/**
	 * @var array Cache of regex and their TTL.
	 */
	protected $_regexCache;

	/**
	 * @var int
	 */
	protected $_categoryID = Category::CAT_MISC;

	/**
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null,
			'Table_Name' => '',
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
		$this->tableName = $options['Table_Name'];
	}

	/**
	 * Add a new regex.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function addRegex(array $data)
	{
		return (bool)$this->pdo->queryInsert(
			sprintf(
				'INSERT INTO %s (group_regex, regex, status, description, ordinal%s) VALUES (%s, %s, %d, %s, %d%s)',
				$this->tableName,
				($this->tableName === 'category_regexes' ? ', category_id' : ''),
				trim($this->pdo->escapeString($data['group_regex'])),
				trim($this->pdo->escapeString($data['regex'])),
				$data['status'],
				trim($this->pdo->escapeString($data['description'])),
				$data['ordinal'],
				($this->tableName === 'category_regexes' ? (', ' . $data['category_id']) : '')
			)
		);
	}

	/**
	 * Update a regex with new info.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function updateRegex(array $data)
	{
		return (bool)$this->pdo->queryExec(
			sprintf(
				'UPDATE %s
				SET group_regex = %s, regex = %s, status = %d, description = %s, ordinal = %d %s
				WHERE id = %d',
				$this->tableName,
				trim($this->pdo->escapeString($data['group_regex'])),
				trim($this->pdo->escapeString($data['regex'])),
				$data['status'],
				trim($this->pdo->escapeString($data['description'])),
				$data['ordinal'],
				($this->tableName === 'category_regexes' ? (', category_id = ' . $data['category_id']) : ''),
				$data['id']
			)
		);
	}

	/**
	 * Get a single regex using its id.
	 *
	 * @param int $id
	 *
	 * @return array
	 */
	public function getRegexByID($id)
	{
		return $this->pdo->queryOneRow(sprintf('SELECT * FROM %s WHERE id = %d', $this->tableName, $id));
	}

	/**
	 * Get all regex.
	 *
	 * @param string $group_regex Optional, a keyword to find a group.
	 * @param int    $limit       Optional, amount of results to limit.
	 * @param int    $offset      Optional, the offset to use when limiting the result set.
	 *
	 * @return array
	 */
	public function getRegex($group_regex = '', $limit = 0, $offset = 0)
	{
		return $this->pdo->query(
			sprintf(
				'SELECT * FROM %s %s ORDER BY id %s',
				$this->tableName,
				$this->_groupQueryString($group_regex),
				($limit ? ('LIMIT ' . $limit . ' OFFSET ' . $offset) : '')
			)
		);
	}

	/**
	 * Get the count of regex in the DB.
	 *
	 * @param string $group_regex Optional, keyword to find a group.
	 *
	 * @return int
	 */
	public function getCount($group_regex = '')
	{
		$query = $this->pdo->queryOneRow(
			sprintf(
				'SELECT COUNT(id) AS count FROM %s %s',
				$this->tableName,
				$this->_groupQueryString($group_regex)
			)
		);
		return (int)$query['count'];
	}

	/**
	 * Delete a regex using its id.
	 *
	 * @param int $id
	 */
	public function deleteRegex($id)
	{
		$this->pdo->queryExec(sprintf('DELETE FROM %s WHERE id = %d', $this->tableName, $id));
	}

	/**
	 * Test a single collection regex for a group name.
	 *
	 * Requires table per group to be on.
	 *
	 * @param string $groupName
	 * @param string $regex
	 * @param int    $limit
	 *
	 * @return array
	 */
	public function testCollectionRegex($groupName, $regex, $limit)
	{
		$groups = new Groups(['Settings' => $this->pdo]);
		$groupID = $groups->getIDByName($groupName);

		if (!$groupID) {
			return [];
		}

		$tableNames = $groups->getCBPTableNames(true, $groupID);

		$rows = $this->pdo->query(
			sprintf(
				'SELECT
					b.name, b.totalparts, b.currentparts, b.binaryhash,
					c.fromname, c.collectionhash
				FROM %s b
				INNER JOIN %s c ON c.id = b.collection_id',
				$tableNames['bname'], $tableNames['cname']
			)
		);

		$data = [];
		if ($rows) {
			$limit--;
			$hashes = [];
			foreach ($rows as $row) {
				if (preg_match($regex, $row['name'], $matches)) {
					ksort($matches);
					$string = $string2 = '';
					foreach ($matches as $key => $match) {
						if (!is_int($key)) {
							$string .= $match;
							$string2 .= '<br/>' . $key . ': ' . $match;
						}
					}
					$files = 0;
					if (preg_match('/[[(\s](\d{1,5})(\/|[\s_]of[\s_]|-)(\d{1,5})[])\s$:]/i', $row['name'], $fileCount)) {
						$files = $fileCount[3];
					}
					$newCollectionHash = sha1($string . $row['fromname'] . $groupID . $files);
					$data['New hash: ' . $newCollectionHash . $string2][$row['binaryhash']] = [
						'file_name'           => $row['name'],
						'file_total_parts'    => $row['totalparts'],
						'file_current_parts'  => $row['currentparts'],
						'collection_poster'   => $row['fromname'],
						'old_collection_hash' => $row['collectionhash'],
					];

					if ($limit > 0) {
						if (count($hashes) > $limit) {
							break;
						}
						$hashes[$newCollectionHash] = '';
					}
				}
			}
		}
		return $data;
	}

	/**
	 * Test a single release naming regex for a group name.
	 *
	 * @param string $groupName
	 * @param string $regex
	 * @param int    $displayLimit
	 * @param int    $queryLimit
	 *
	 * @return array
	 */
	public function testReleaseNamingRegex($groupName, $regex, $displayLimit, $queryLimit)
	{
		$groups = new Groups(['Settings' => $this->pdo]);
		$groupID = $groups->getIDByName($groupName);

		if (!$groupID) {
			return [];
		}

		$rows = $this->pdo->query(
			sprintf(
				'SELECT name, searchname, id FROM releases WHERE group_id = %d LIMIT %d',
				$groupID,
				$queryLimit
			)
		);

		$data = [];
		if ($rows) {
			$limit = 1;
			foreach ($rows as $row) {
				$match = $this->_matchRegex($regex, $row['name']);
				if ($match) {
					$data[$row['id']] = [
						'subject'  => $row['name'],
						'old_name' => $row['searchname'],
						'new_name' => $match
					];
					if ($limit++ > $displayLimit) {
						break;
					}
				}
			}
		}
		return $data;
	}

	/**
	 * This will try to find regex in the DB for a group and a usenet subject, attempt to match them and return the matches.
	 *
	 * @param string $subject
	 * @param string $groupName
	 *
	 * @return string
	 */
	public function tryRegex($subject, $groupName)
	{
		$this->_fetchRegex($groupName);

		$returnString = '';
		// If there are no regex, return and try regex in this file.
		if ($this->_regexCache[$groupName]['regex']) {
			foreach ($this->_regexCache[$groupName]['regex'] as $regex) {

				if ($this->tableName === 'category_regexes') {
					$this->_categoryID = $regex['category_id'];
				}

				$returnString = $this->_matchRegex($regex['regex'], $subject);
				// If this regex found something, break and return, or else continue trying other regex.
				if ($returnString) {
					break;
				}
			}
		}
		return $returnString;
	}

	/**
	 * Get the regex from the DB, cache them locally for 15 mins.
	 * Cache them also in the cache server, as this script might be terminated.
	 *
	 * @param string $groupName
	 */
	protected function _fetchRegex($groupName)
	{
		// Check if we need to do an initial cache or refresh our cache.
		if (isset($this->_regexCache[$groupName]['ttl']) && (time() - $this->_regexCache[$groupName]['ttl']) < 900) {
			return;
		}

		// Get all regex from DB which match the current group name. Cache them for 15 minutes. #CACHEDQUERY#
		$this->_regexCache[$groupName]['regex'] = $this->pdo->query(
			sprintf(
				'SELECT r.regex%s FROM %s r WHERE %s REGEXP r.group_regex AND r.status = 1 ORDER BY r.ordinal ASC, r.group_regex ASC',
				($this->tableName === 'category_regexes' ? ', r.category_id' : ''),
				$this->tableName,
				$this->pdo->escapeString($groupName)
			), true, 900
		);
		// Set the TTL.
		$this->_regexCache[$groupName]['ttl'] = time();
	}

	/**
	 * Find matches on a regex taken from the database.
	 *
	 * Requires at least 1 named captured group.
	 *
	 * @param string $regex
	 * @param string $subject
	 *
	 * @return string
	 */
	protected function _matchRegex($regex, $subject)
	{
		$returnString = '';
		if (preg_match($regex, $subject, $matches)) {
			if (count($matches) > 0) {
				// Sort the keys, the named key matches will be concatenated in this order.
				ksort($matches);
				foreach ($matches as $key => $value) {
					switch ($this->tableName) {
						case 'collection_regexes': // Put this at the top since it's the most important for performance.
						case 'release_naming_regexes':
							// Ignore non-named capture groups. Only named capture groups are important.
							if (is_int($key) || preg_match('#reqid|parts#i', $key)) {
								continue 2;
							}
							$returnString .= $value; // Concatenate the string to return.
							break;
						case 'category_regexes':
							$returnString = $this->_categoryID; // Regex matched, so return the category ID.
							break 2;
					}
				}
			}
		}
		return $returnString;
	}

	/**
	 * Format part of a query.
	 *
	 * @param string $group_regex
	 *
	 * @return string
	 */
	protected function _groupQueryString($group_regex)
	{
		return ($group_regex ? ('WHERE group_regex ' . $this->pdo->likeString($group_regex)) : '');
	}
}
