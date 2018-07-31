<?php
namespace nzedb;

use nzedb\db\DB;

class Menu
{
	/**
	 * @var \nzedb\db\DB
	 */
	public $pdo;

	/**
	 * Menu constructor.
	 *
	 * @param null $pdo
	 *
	 * @throws \RuntimeException
	 */
	public function __construct($pdo = null)
	{
		$this->pdo = ($pdo instanceof DB ? $pdo : new DB());
	}

	/**
	 * @param $role
	 * @param $serverurl
	 *
	 * @return array
	 */
	public function get($role, $serverurl)
	{
		$guest = '';
		if ($role !== Users::ROLE_GUEST) {
			$guest = sprintf(' AND role != %d ', Users::ROLE_GUEST);
		}

		if ($role !== Users::ROLE_ADMIN) {
			$guest .= sprintf(' AND role != %d ', Users::ROLE_ADMIN);
		}

		$data = $this->pdo->query(sprintf('SELECT * FROM menu_items WHERE role <= %d %s ORDER BY ordinal', $role, $guest));

		$ret = [];
		foreach ($data as $d) {
			if (stripos($d['href'], 'http') === false) {
				$d['href'] = $serverurl . $d['href'];
				$ret[] = $d;
			} else {
				$ret[] = $d;
			}
		}
		return $ret;
	}

	/**
	 * @return array
	 */
	public function getAll()
	{
		return $this->pdo->query('SELECT * FROM menu_items ORDER BY role, ordinal');
	}

	/**
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getById($id)
	{
		return $this->pdo->queryOneRow(sprintf('SELECT * FROM menu_items WHERE id = %d', $id));
	}

	/**
	 * @param $id
	 *
	 * @return bool|\PDOStatement
	 */
	public function delete($id)
	{
		return $this->pdo->queryExec(sprintf('DELETE FROM menu_items WHERE id = %d', $id));
	}

	/**
	 * @param $menu
	 *
	 * @return false|int|string
	 */
	public function add($menu)
	{
		return $this->pdo->queryInsert(sprintf('INSERT INTO menu_items (href, title, tooltip, role, ordinal, menueval, newwindow ) VALUES (%s, %s,  %s, %d, %d, %s, %d)', $this->pdo->escapeString($menu["href"]), $this->pdo->escapeString($menu["title"]), $this->pdo->escapeString($menu["tooltip"]), $menu["role"], $menu["ordinal"], $this->pdo->escapeString($menu["menueval"]), $menu["newwindow"]));
	}

	/**
	 * @param $menu
	 *
	 * @return bool|\PDOStatement
	 */
	public function update($menu)
	{
		return $this->pdo->queryExec(sprintf('UPDATE menu_items SET href = %s, title = %s, tooltip = %s, role = %d, ordinal = %d, menueval = %s, newwindow = %d WHERE id = %d', $this->pdo->escapeString($menu["href"]), $this->pdo->escapeString($menu["title"]), $this->pdo->escapeString($menu["tooltip"]), $menu["role"], $menu["ordinal"], $this->pdo->escapeString($menu["menueval"]), $menu["newwindow"], $menu["id"]));
	}
}
