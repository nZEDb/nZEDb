<?php
require_once(WWW_DIR."/lib/framework/db.php");

class Tmux
{

	public function version()
	{
		return "0.0.2";
	}

	public function update($form)
	{
		$db = new DB();
		$tmux = $this->row2Object($form);

		$sql = $sqlKeys = array();
		foreach($form as $settingK=>$settingV)
		{
			$sql[] = sprintf("WHEN %s THEN %s", $db->escapeString($settingK), $db->escapeString($settingV));
			$sqlKeys[] = $db->escapeString($settingK);
		}

		$db->queryExec(sprintf("UPDATE tmux SET value = CASE setting %s END WHERE setting IN (%s)", implode(' ', $sql), implode(', ', $sqlKeys)));

		return $tmux;
	}

	public function get()
	{
		$db = new DB();
		$rows = $db->query("SELECT * FROM tmux");

		if ($rows === false)
			return false;

		return $this->rows2Object($rows);
	}

	public function rows2Object($rows)
	{
		$obj = new stdClass;
		foreach($rows as $row)
			$obj->{$row['setting']} = $row['value'];

		$obj->{'version'} = $this->version();
		return $obj;
	}

	public function row2Object($row)
	{
		$obj = new stdClass;
		$rowKeys = array_keys($row);
		foreach($rowKeys as $key)
			$obj->{$key} = $row[$key];

		return $obj;
	}

	public function updateItem($setting, $value)
	{
		$db = new DB();
		$sql = sprintf("UPDATE tmux SET value = %s WHERE setting = %s", $db->escapeString($value), $db->escapeString($setting));
		return $db->queryExec($sql);
	}
}
