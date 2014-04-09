<?php

use nzedb\db\DB;

class Contents
{
	const TYPEUSEFUL = 1;
	const TYPEARTICLE = 2;
	const TYPEINDEX = 3;

	public function get()
	{
		$arr = array();
		$rows = $this->data_get();
		if ($rows === false) {
			return false;
		}

		foreach ($rows as $row) {
			$arr[] = $this->row2Object($row);
		}

		return $arr;
	}

	public function getAll()
	{
		$arr = array();
		$rows = $this->data_getAll();
		if ($rows === false) {
			return false;
		}

		foreach ($rows as $row) {
			$arr[] = $this->row2Object($row);
		}

		return $arr;
	}

	/**
	 * Convert get all but from to object.
	 *
	 * @return array|bool
	 */
	public function getAllButFront()
	{
		$arr = array();
		$rows = $this->data_getAllButFront();
		if ($rows === false) {
			return false;
		}

		foreach ($rows as $row) {
			$arr[] = $this->row2Object($row);
		}

		return $arr;
	}

	public function getFrontPage()
	{
		$arr = array();
		$rows = $this->data_getFrontPage();
		if ($rows === false) {
			return false;
		}

		foreach ($rows as $row) {
			$arr[] = $this->row2Object($row);
		}

		return $arr;
	}

	public function getForMenuByTypeAndRole($id, $role)
	{

		$arr = array();
		$rows = $this->data_getForMenuByTypeAndRole($id, $role);
		if ($rows === false) {
			return false;
		}

		foreach ($rows as $row) {
			$arr[] = $this->row2Object($row);
		}

		return $arr;
	}

	public function getIndex()
	{
		$row = $this->data_getIndex();
		if ($row === false) {
			return false;
		}

		return $this->row2Object($row);
	}

	public function getByID($id, $role)
	{
		$row = $this->data_getByID($id, $role);
		if ($row === false) {
			return false;
		}

		return $this->row2Object($row);
	}

	public function validate($content)
	{
		if (substr($content->url, 0, 1) != '/') {
			$content->url = "/" . $content->url;
		}

		if (substr($content->url, strlen($content->url) - 1) != '/') {
			$content->url = $content->url . "/";
		}

		return $content;
	}

	public function add($form)
	{
		$content = $this->row2Object($form);
		$content = $this->validate($content);
		$db = new DB();
		if ($content->ordinal == 1) {
			$db->queryDirect("UPDATE content SET ordinal = ordinal + 1 WHERE ordinal > 0");
		}
		return $this->data_add($content);
	}

	public function delete($id)
	{
		$db = new DB();
		return $db->queryExec(sprintf("DELETE FROM content WHERE id = %d", $id));
	}

	public function update($form)
	{
		$content = $this->row2Object($form);
		$content = $this->validate($content);
		$this->data_update($content);

		return $content;
	}

	public function row2Object($row, $prefix = "")
	{
		$obj = new Content();
		if (isset($row[$prefix . "id"])) {
			$obj->id = $row[$prefix . "id"];
		}
		$obj->title = $row[$prefix . "title"];
		$obj->url = $row[$prefix . "url"];
		$obj->body = $row[$prefix . "body"];
		$obj->metadescription = $row[$prefix . "metadescription"];
		$obj->metakeywords = $row[$prefix . "metakeywords"];
		$obj->contenttype = $row[$prefix . "contenttype"];
		$obj->showinmenu = $row[$prefix . "showinmenu"];
		$obj->status = $row[$prefix . "status"];
		$obj->ordinal = $row[$prefix . "ordinal"];
		if (isset($row[$prefix . "createddate"])) {
			$obj->createddate = $row[$prefix . "createddate"];
		}
		$obj->role = $row[$prefix . "role"];
		return $obj;
	}

	public function data_update($content)
	{
		$db = new DB();
		return $db->queryExec(sprintf("UPDATE content SET role = %d, title = %s, url = %s, body = %s, metadescription = %s, metakeywords = %s, contenttype = %d, showinmenu = %d, status = %d, ordinal = %d WHERE id = %d", $content->role, $db->escapeString($content->title), $db->escapeString($content->url), $db->escapeString($content->body), $db->escapeString($content->metadescription), $db->escapeString($content->metakeywords), $content->contenttype, $content->showinmenu, $content->status, $content->ordinal, $content->id));
	}

	public function data_add($content)
	{
		$db = new DB();
		return $db->queryInsert(sprintf("INSERT INTO content (role, title, url, body, metadescription, metakeywords, contenttype, showinmenu, status, ordinal) values (%d, %s, %s, %s, %s, %s, %d, %d, %d, %d )", $content->role, $db->escapeString($content->title), $db->escapeString($content->url), $db->escapeString($content->body), $db->escapeString($content->metadescription), $db->escapeString($content->metakeywords), $content->contenttype, $content->showinmenu, $content->status, $content->ordinal));
	}

	public function data_get()
	{
		$db = new DB();
		return $db->query(sprintf("SELECT * FROM content WHERE status = 1 ORDER BY contenttype, COALESCE(ordinal, 1000000)"));
	}

	public function data_getAll()
	{
		$db = new DB();
		return $db->query(sprintf("SELECT * FROM content ORDER BY contenttype, COALESCE(ordinal, 1000000)"));
	}

	/**
	 * Get all but front page.
	 *
	 * @return array
	 */
	public function data_getAllButFront()
	{
		$db = new DB();
		return $db->query(sprintf("SELECT * FROM content WHERE id != 1 ORDER BY contenttype, COALESCE(ordinal, 1000000)"));
	}

	public function data_getByID($id, $role)
	{
		$db = new DB();
		if ($role == Users::ROLE_ADMIN) {
			$role = "";
		} else {
			$role = sprintf("AND (role = %d OR role = 0)", $role);
		}

		return $db->queryOneRow(sprintf("SELECT * FROM content WHERE id = %d %s", $id, $role));
	}

	public function data_getFrontPage()
	{
		$db = new DB();
		return $db->query(sprintf("SELECT * FROM content WHERE status = 1 AND contenttype = %d ORDER BY ordinal ASC, COALESCE(ordinal, 1000000), id", Contents::TYPEINDEX));
	}

	public function data_getIndex()
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM content WHERE status = 1 AND contenttype = %d", Contents::TYPEINDEX));
	}

	public function data_getForMenuByTypeAndRole($id, $role)
	{
		$db = new DB();
		if ($role == Users::ROLE_ADMIN) {
			$role = "";
		} else {
			$role = sprintf("AND (role = %d OR role = 0)", $role);
		}
		return $db->query(sprintf("SELECT * FROM content WHERE showinmenu = 1 AND status = 1 AND contenttype = %d %s ", $id, $role));
	}
}
