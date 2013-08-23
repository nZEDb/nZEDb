<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/users.php");

class Menu
{
	public function get($role, $serverurl)
	{
		$db = new DB();

		$guest = "";
		if ($role != Users::ROLE_GUEST)
			$guest = sprintf(" and role != %d ", Users::ROLE_GUEST);

		if ($role != Users::ROLE_ADMIN)
			$guest .= sprintf(" and role != %d ", Users::ROLE_ADMIN);

		$sql = sprintf("select * from menu where role <= %d %s order by ordinal", $role, $guest);

		$data = $db->query($sql);

		$ret = array();
		foreach ($data as $d)
		{
			if (!preg_match("/http/i", $d["href"]))
			{
				$d["href"] = $serverurl.$d["href"];
				$ret[] = $d;
			}
			else
			{
				$ret[] = $d;
			}
		}
		return $ret;
	}

	public function getAll()
	{
		$db = new DB();
		return $db->query(sprintf("select * from menu order by role, ordinal"));
	}

	public function getById($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("select * from menu where ID = %d", $id));
	}

	public function delete($id)
	{
		$db = new DB();
		return $db->queryDelete(sprintf("delete from menu where ID = %d", $id));
	}

	public function add($menu)
	{
		$db = new DB();
		return $db->queryInsert(sprintf("INSERT INTO menu (href, title, tooltip, role, ordinal, menueval, newwindow )
			VALUES (%s, %s,  %s, %d, %d, %s, %d) ", $db->escapeString($menu["href"]), $db->escapeString($menu["title"]), $db->escapeString($menu["tooltip"]), $menu["role"] , $menu["ordinal"], $db->escapeString($menu["menueval"]), $menu["newwindow"] ));
	}

	public function update($menu)
	{
		$db = new DB();
		return $db->queryUpdate(sprintf("update menu set href = %s, title = %s, tooltip = %s, role = %d, ordinal = %d, menueval = %s, newwindow=%d where ID = %d	", $db->escapeString($menu["href"]), $db->escapeString($menu["title"]), $db->escapeString($menu["tooltip"]), $menu["role"] , $menu["ordinal"], $db->escapeString($menu["menueval"]), $menu["newwindow"], $menu["id"]  ));
	}
}
