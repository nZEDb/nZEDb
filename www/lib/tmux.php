<?php
require_once(WWW_DIR."/lib/framework/db.php");

class Tmux
{

	public function update($form)
	{
		$db = new DB();
		$tmux = $this->row2Object($form);
		
		$sql = $sqlKeys = array();
		foreach($form as $settingK=>$settingV)
		{
			$sql[] = sprintf("WHEN %s THEN %s", $db->escapeString($settingK), $db->escapeString(trim($settingV)));
			$sqlKeys[] = $db->escapeString($settingK);
		}

		$db->query(sprintf("UPDATE tmux SET value = CASE setting %s END WHERE setting IN (%s)", implode(' ', $sql), implode(', ', $sqlKeys)));	

		return $tmux;
	}

	public function get()
	{			
		$db = new DB();
		$rows = $db->query("select * from tmux");			

		if ($rows === false)
			return false;
		
		return $this->rows2Object($rows);
	}	
	
	public function rows2Object($rows)
	{
		$obj = new stdClass;
		foreach($rows as $row)
			$obj->{$row['setting']} = $row['value'];
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
		$sql = sprintf("update tmux set value = %s where setting = %s", $db->escapeString($value), $db->escapeString($setting));
		return $db->query($sql);
	}	
}
