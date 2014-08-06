<?php

class ReleaseSearch
{
	const FULLTEXT = 0;
	const LIKE     = 1;
	const SPHINX   = 2;

	/***
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * The string of words to search.
	 * @var string
	 */
	private $searchString;

	/**
	 * Name of the column. ie name | searchname | fromname | etc.
	 * @var string
	 */
	private $columnName;

	/**
	 * Sets the string to join the releases table to the release search table if using full text.
	 * @var string
	 */
	private $fullTextJoinString;

	/**
	 * @param \nzedb\db\Settings $settings
	 */
	public function __construct(nzedb\db\Settings $settings)
	{
		if (!defined('nZEDb_RELEASE_SEARCH_TYPE')) {
			define('nZEDb_RELEASE_SEARCH_TYPE', self::FULLTEXT);
		}

		switch (nZEDb_RELEASE_SEARCH_TYPE) {
			case self::LIKE:
				$this->fullTextJoinString = '';
				break;
			case self::SPHINX: // Set sphinx as fulltext for now.
			case self::FULLTEXT:
			default:
			$this->fullTextJoinString = 'INNER JOIN releasesearch rs on rs.releaseid = r.id';
				break;
		}

		$this->pdo = ($settings instanceof nzedb\db\Settings ? $settings : new nzedb\db\Settings());
	}

	/**
	 * Create part of a SQL query for searching releases.
	 *
	 * @param string $columnName   Name of the column. ie name | searchname | fromname | etc.
	 * @param string $searchString The string of words to search.
	 * @param bool   $forceLike    Force a "like" search on the column.
	 *
	 * @return string
	 */
	public function getSearchSQL($columnName, $searchString, $forceLike = false)
	{
		$this->columnName = $columnName;
		$this->searchString = $searchString;

		if ($forceLike) {
			return $this->likeSQL();
		}

		switch (nZEDb_RELEASE_SEARCH_TYPE) {
			case self::LIKE:
				$SQL = $this->likeSQL();
				break;
			case self::SPHINX: // Set sphinx as fulltext for now.
			case self::FULLTEXT:
			default:
				$SQL = $this->fullTextSQL();
				break;
		}
		return $SQL;
	}

	/**
	 * Returns the string for joining the release search table to the releases table.
	 * @return string
	 */
	public function getFullTextJoinString()
	{
		return $this->fullTextJoinString;
	}

	/**
	 * Create SQL sub-query for full text searching.
	 *
	 * @return string
	 */
	private function fullTextSQL()
	{
		$searchWords = '';

		// At least 1 search term needs to be mandatory.
		$words = explode(' ', (!preg_match('/[+!^]/', $this->searchString) ? '+' : '') . $this->searchString);
		foreach ($words as $word) {
			$word = str_replace("'", "\\'", str_replace(['!', '^'], '+', trim($word, "-\n\t\r\0\x0B ")));

			if ($word !== '' && $word !== '-' && strlen($word) >= 2) {
				$searchWords .= sprintf('%s ', $word);
			}
		}

		$searchWords = trim($searchWords);
		// If we didn't get anything, try the LIKE method.
		if ($searchWords === '') {
			return ($this->likeSQL());
		} else {
			return sprintf(" AND MATCH(rs.%s) AGAINST('%s' IN BOOLEAN MODE)", $this->columnName, $searchWords);
		}
	}

	/**
	 * Create SQL sub-query for standard search.
	 *
	 * @return string
	 */
	private function likeSQL()
	{
		$searchSQL = '';
		$wordCount = 0;
		$words = explode(' ', $this->searchString);
		foreach ($words as $word) {
			if ($word != '') {
				$word = trim($word, "-\n\t\r\0\x0B ");
				if ($wordCount == 0 && (strpos($word, '^') === 0)) {
					$searchSQL .= sprintf(' AND r.%s %s', $this->columnName, $this->pdo->likeString(substr($word, 1), false));
				} else if (substr($word, 0, 2) == '--') {
					$searchSQL .= sprintf(' AND r.%s NOT %s', $this->columnName, $this->pdo->likeString(substr($word, 2)));
				} else {
					$searchSQL .= sprintf(' AND r.%s %s', $this->columnName, $this->pdo->likeString($word));
				}
				$wordCount++;
			}
		}
		return $searchSQL;
	}
}