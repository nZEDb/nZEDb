<?php

class Sites
{

	const REGISTER_STATUS_OPEN = 0;
	const REGISTER_STATUS_INVITE = 1;
	const REGISTER_STATUS_CLOSED = 2;
	const REGISTER_STATUS_API_ONLY = 3;
	const ERR_BADUNRARPATH = -1;
	const ERR_BADFFMPEGPATH = -2;
	const ERR_BADMEDIAINFOPATH = -3;
	const ERR_BADNZBPATH = -4;
	const ERR_DEEPNOUNRAR = -5;
	const ERR_BADTMPUNRARPATH = -6;
	const ERR_BADNZBPATH_UNREADABLE = -7;
	const ERR_BADNZBPATH_UNSET = -8;

	function __construct()
	{
		$this->db = new DB();
	}

	public function version()
	{
		return "0.0.3";
	}

	public function update($form)
	{
		$db = $this->db;
		$site = $this->row2Object($form);

		if (substr($site->nzbpath, strlen($site->nzbpath) - 1) != '/') {
			$site->nzbpath = $site->nzbpath . "/";
		}

		//
		// Validate site settings
		//
		if ($site->mediainfopath != "" && !is_file($site->mediainfopath)) {
			return Sites::ERR_BADMEDIAINFOPATH;
		}

		if ($site->ffmpegpath != "" && !is_file($site->ffmpegpath)) {
			return Sites::ERR_BADFFMPEGPATH;
		}

		if ($site->unrarpath != "" && !is_file($site->unrarpath)) {
			return Sites::ERR_BADUNRARPATH;
		}

		if (empty($site->nzbpath)) {
			return Sites::ERR_BADNZBPATH_UNSET;
		}

		if (!file_exists($site->nzbpath) || !is_dir($site->nzbpath)) {
			return Sites::ERR_BADNZBPATH;
		}

		if (!is_readable($site->nzbpath)) {
			return Sites::ERR_BADNZBPATH_UNREADABLE;
		}

		if ($site->checkpasswordedrar == 1 && !is_file($site->unrarpath)) {
			return Sites::ERR_DEEPNOUNRAR;
		}

		if ($site->tmpunrarpath != "" && !file_exists($site->tmpunrarpath)) {
			return Sites::ERR_BADTMPUNRARPATH;
		}

		$sql = $sqlKeys = array();
		foreach ($form as $settingK => $settingV) {
			$sql[] = sprintf("WHEN %s THEN %s", $db->escapeString($settingK), $db->escapeString($settingV));
			$sqlKeys[] = $db->escapeString($settingK);
		}

		$db->queryExec(sprintf("UPDATE site SET value = CASE setting %s END WHERE setting IN (%s)", implode(' ', $sql), implode(', ', $sqlKeys)));

		return $site;
	}

	public function get()
	{
		$db = $this->db;
		$rows = $db->query("SELECT * FROM site");

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

	public function getLicense($html = false)
	{
		$n = "\r\n";
		if ($html) {
			$n = "<br/>";
		}

		return $n . "nZEDb " . $this->version() . $n . "

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation." . $n . "

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
" . $n;
	}

}
