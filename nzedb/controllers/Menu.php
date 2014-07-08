<?php

use nzedb\db\Settings;

class Menu
{
	public function get($role, $serverurl)
	{
		$pdo = new Settings();

		$guest = "";
		if ($role != Users::ROLE_GUEST) {
			$guest = sprintf(" AND role != %d ", Users::ROLE_GUEST);
		}

		if ($role != Users::ROLE_ADMIN) {
			$guest .= sprintf(" AND role != %d ", Users::ROLE_ADMIN);
		}

		$data = $pdo->query(sprintf("SELECT * FROM menu WHERE role <= %d %s ORDER BY ordinal", $role, $guest));

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
		$pdo = new Settings();
		return $pdo->query("SELECT * FROM menu ORDER BY role, ordinal");
	}

	public function getById($id)
	{
		$pdo = new Settings();
		return $pdo->queryOneRow(sprintf("SELECT * FROM menu WHERE id = %d", $id));
	}

	public function delete($id)
	{
		$pdo = new Settings();
		return $pdo->queryExec(sprintf("DELETE FROM menu WHERE id = %d", $id));
	}

	public function add($menu)
	{
		$pdo = new Settings();
		return $pdo->queryInsert(sprintf("INSERT INTO menu (href, title, tooltip, role, ordinal, menueval, newwindow ) VALUES (%s, %s,  %s, %d, %d, %s, %d)", $pdo->escapeString($menu["href"]), $pdo->escapeString($menu["title"]), $pdo->escapeString($menu["tooltip"]), $menu["role"], $menu["ordinal"], $pdo->escapeString($menu["menueval"]), $menu["newwindow"]));
	}

	public function update($menu)
	{
		$pdo = new Settings();
		return $pdo->queryExec(sprintf("UPDATE menu SET href = %s, title = %s, tooltip = %s, role = %d, ordinal = %d, menueval = %s, newwindow = %d WHERE id = %d", $pdo->escapeString($menu["href"]), $pdo->escapeString($menu["title"]), $pdo->escapeString($menu["tooltip"]), $menu["role"], $menu["ordinal"], $pdo->escapeString($menu["menueval"]), $menu["newwindow"], $menu["id"]));
	}
}
