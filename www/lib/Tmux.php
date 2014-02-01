<?php

class Tmux
{
	function __construct()
	{
		$this->db = new DB();
	}

	public function version()
	{
		$s = new Sites();
		$site = $s->get();
		return $site->version;
	}

	public function update($form)
	{
		$db = $this->db;
		$tmux = $this->row2Object($form);

		$sql = $sqlKeys = array();
		foreach ($form as $settingK => $settingV) {
			if (is_array($settingV)) {
				$settingV = implode(', ', $settingV);
			}
			$sql[] = sprintf("WHEN %s THEN %s", $db->escapeString($settingK), $db->escapeString($settingV));
			$sqlKeys[] = $db->escapeString($settingK);
		}

		$db->queryExec(sprintf("UPDATE tmux SET value = CASE setting %s END WHERE setting IN (%s)", implode(' ', $sql), implode(', ', $sqlKeys)));

		return $tmux;
	}

	public function get()
	{
		$db = $this->db;
		$rows = $db->query("SELECT * FROM tmux");

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
		$db = $this->db;
		$sql = sprintf("UPDATE tmux SET value = %s WHERE setting = %s", $db->escapeString($value), $db->escapeString($setting));
		return $db->queryExec($sql);
	}
}
?>
