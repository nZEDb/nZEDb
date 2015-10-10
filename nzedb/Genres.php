<?php
namespace nzedb;

use nzedb\db\Settings;

class Genres
{
	const CONSOLE_TYPE = Category::CAT_PARENT_GAME;
	const MUSIC_TYPE   = Category::CAT_PARENT_MUSIC;
	const GAME_TYPE    = Category::CAT_PARENT_PC;

	const STATUS_ENABLED = 0;
	const STATUS_DISABLED = 1;

	/**
	 * @var \nzedb\db\Settings;
	 */
	public $pdo;

	/**
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
	}

	public function getGenres($type = '', $activeonly = false)
	{
		return $this->pdo->query($this->getListQuery($type, $activeonly), true, nZEDb_CACHE_EXPIRY_LONG);
	}

	private function getListQuery($type = '', $activeonly = false)
	{
		if (!empty($type)) {
			$typesql = sprintf(" AND g.type = %d", $type);
		} else {
			$typesql = '';
		}

		if ($activeonly) {
			$sql = sprintf("SELECT g.* FROM genres g WHERE 1 %s AND g.disabled != 1 ORDER BY g.title", $typesql
			);
		} else {
			$sql = sprintf("SELECT g.* FROM genres g WHERE 1 %s ORDER BY g.title", $typesql);
		}

		return $sql;
	}

	public function getRange($start, $num, $type = '', $activeonly = false)
	{
		$sql = $this->getListQuery($type, $activeonly);
		$sql .= " LIMIT $num OFFSET $start";
		return $this->pdo->query($sql);
	}

	public function getCount($type = '', $activeonly = false)
	{
		if (!empty($type)) {
			$typesql = sprintf(" AND g.type = %d", $type);
		} else {
			$typesql = '';
		}

		if ($activeonly) {
			$sql = sprintf("SELECT COUNT(*) AS num FROM genres g WHERE 1 %s AND g.disabled != 1", $typesql
			);
		} else {
			$sql = sprintf("SELECT COUNT(*) AS num FROM genres g WHERE 1 %s", $typesql);
		}

		$res = $this->pdo->queryOneRow($sql);
		return $res["num"];
	}

	public function getById($id)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT * FROM genres WHERE id = %d", $id));
	}

	public function update($id, $disabled)
	{
		return $this->pdo->queryExec(sprintf("UPDATE genres SET disabled = %d WHERE id = %d", $disabled, $id));
	}

	public function getDisabledIDs()
	{
		return $this->pdo->query("SELECT id FROM genres WHERE disabled = 1", true, nZEDb_CACHE_EXPIRY_LONG);
	}
}
