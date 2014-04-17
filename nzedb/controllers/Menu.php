<?php

use nzedb\db\DB;

class Menu
{
	public function get($role, $serverurl)
	{
		$db = new DB();

		$guest = "";
		if ($role != Users::ROLE_GUEST) {
			$guest = sprintf(" AND role != %d ", Users::ROLE_GUEST);
		}

		if ($role != Users::ROLE_ADMIN) {
			$guest .= sprintf(" AND role != %d ", Users::ROLE_ADMIN);
		}

		$data = $db->query(sprintf("SELECT * FROM menu WHERE role <= %d %s ORDER BY ordinal", $role, $guest));

		$ret = array();
		foreach ($data as $d) {
			if (!preg_match("/http/i", $d["href"])) {
				$d["href"] = $serverurl . $d["href"];
				$ret[] = $d;
			} else {
				$ret[] = $d;
			}
		}
		return $ret;
	}

	public function getAll()
	{
		$db = new DB();
		return $db->query("SELECT * FROM menu ORDER BY role, ordinal");
	}

	public function getById($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM menu WHERE id = %d", $id));
	}

	public function delete($id)
	{
		$db = new DB();
		return $db->queryExec(sprintf("DELETE FROM menu WHERE id = %d", $id));
	}

	public function add($menu)
	{
		$db = new DB();
		return $db->queryInsert(sprintf("INSERT INTO menu (href, title, tooltip, role, ordinal, menueval, newwindow ) VALUES (%s, %s,  %s, %d, %d, %s, %d)", $db->escapeString($menu["href"]), $db->escapeString($menu["title"]), $db->escapeString($menu["tooltip"]), $menu["role"], $menu["ordinal"], $db->escapeString($menu["menueval"]), $menu["newwindow"]));
	}

	public function update($menu)
	{
		$db = new DB();
		return $db->queryExec(sprintf("UPDATE menu SET href = %s, title = %s, tooltip = %s, role = %d, ordinal = %d, menueval = %s, newwindow = %d WHERE id = %d", $db->escapeString($menu["href"]), $db->escapeString($menu["title"]), $db->escapeString($menu["tooltip"]), $menu["role"], $menu["ordinal"], $db->escapeString($menu["menueval"]), $menu["newwindow"], $menu["id"]));
	}
}
