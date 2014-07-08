<?php

use nzedb\db\Settings;

class Tmux
{
	public $pdo;

	function __construct()
	{
		$this->pdo = new Settings();
	}

	public function version()
	{
		return $this->pdo->version();
	}

	public function update($form)
	{
		$pdo = $this->pdo;
		$tmux = $this->row2Object($form);

		$sql = $sqlKeys = array();
		foreach ($form as $settingK => $settingV) {
			if (is_array($settingV)) {
				$settingV = implode(', ', $settingV);
			}
			$sql[] = sprintf("WHEN %s THEN %s", $pdo->escapeString($settingK), $pdo->escapeString($settingV));
			$sqlKeys[] = $pdo->escapeString($settingK);
		}

		$pdo->queryExec(sprintf("UPDATE tmux SET value = CASE setting %s END WHERE setting IN (%s)", implode(' ', $sql), implode(', ', $sqlKeys)));

		return $tmux;
	}

	public function get()
	{
		$pdo = $this->pdo;
		$rows = $pdo->query("SELECT * FROM tmux");

		if ($rows === false) {
			return false;
		}

		return $this->rows2Object($rows);
	}

	public function rows2Object($rows)
	{
		$obj = new stdClass;
		foreach ($rows as $row) {
			$obj->{$row['setting']} = $row['value'];
		}

		$obj->{'version'} = $this->version();
		return $obj;
	}

	public function row2Object($row)
	{
		$obj = new stdClass;
		$rowKeys = array_keys($row);
		foreach ($rowKeys as $key) {
			$obj->{$key} = $row[$key];
		}

		return $obj;
	}

	public function updateItem($setting, $value)
	{
		$pdo = $this->pdo;
		$sql = sprintf("UPDATE tmux SET value = %s WHERE setting = %s", $pdo->escapeString($value), $pdo->escapeString($setting));
		return $pdo->queryExec($sql);
	}
}
