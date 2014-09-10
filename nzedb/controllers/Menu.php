<?php

use nzedb\db\Settings;

class Menu
{
	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @param nzedb\db\Settings $settings
	 */
	public function __construct($settings = null)
	{
		$this->pdo = ($settings instanceof Settings ? $settings : new Settings());
	}

	public function get($role, $serverurl)
	{
		$guest = "";
		if ($role != \Users::ROLE_GUEST) {
			$guest = sprintf(" AND role != %d ", \Users::ROLE_GUEST);
		}

		if ($role != \Users::ROLE_ADMIN) {
			$guest .= sprintf(" AND role != %d ", \Users::ROLE_ADMIN);
		}

		$data = $this->pdo->query(sprintf("SELECT * FROM menu WHERE role <= %d %s ORDER BY ordinal", $role, $guest));

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
		return $this->pdo->query("SELECT * FROM menu ORDER BY role, ordinal");
	}

	public function getById($id)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT * FROM menu WHERE id = %d", $id));
	}

	public function delete($id)
	{
		return $this->pdo->queryExec(sprintf("DELETE FROM menu WHERE id = %d", $id));
	}

	public function add($menu)
	{
		return $this->pdo->queryInsert(sprintf("INSERT INTO menu (href, title, tooltip, role, ordinal, menueval, newwindow ) VALUES (%s, %s,  %s, %d, %d, %s, %d)", $this->pdo->escapeString($menu["href"]), $this->pdo->escapeString($menu["title"]), $this->pdo->escapeString($menu["tooltip"]), $menu["role"], $menu["ordinal"], $this->pdo->escapeString($menu["menueval"]), $menu["newwindow"]));
	}

	public function update($menu)
	{
		return $this->pdo->queryExec(sprintf("UPDATE menu SET href = %s, title = %s, tooltip = %s, role = %d, ordinal = %d, menueval = %s, newwindow = %d WHERE id = %d", $this->pdo->escapeString($menu["href"]), $this->pdo->escapeString($menu["title"]), $this->pdo->escapeString($menu["tooltip"]), $menu["role"], $menu["ordinal"], $this->pdo->escapeString($menu["menueval"]), $menu["newwindow"], $menu["id"]));
	}
}
