<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2014 nZEDb
 */
namespace nzedb\db;

if (!defined('nZEDb_INSTALLER')) {
	require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'config.php';
}

use nzedb\utility\Utility;
use nzedb\utility\Versions;

class Settings extends DB
{
	const REGISTER_STATUS_OPEN      = 0;
	const REGISTER_STATUS_INVITE    = 1;
	const REGISTER_STATUS_CLOSED    = 2;
	const REGISTER_STATUS_API_ONLY  = 3;
	const ERR_BADUNRARPATH          = -1;
	const ERR_BADFFMPEGPATH         = -2;
	const ERR_BADMEDIAINFOPATH      = -3;
	const ERR_BADNZBPATH            = -4;
	const ERR_DEEPNOUNRAR           = -5;
	const ERR_BADTMPUNRARPATH       = -6;
	const ERR_BADNZBPATH_UNREADABLE = -7;
	const ERR_BADNZBPATH_UNSET      = -8;
	const ERR_BAD_COVERS_PATH       = -9;
	const ERR_BAD_YYDECODER_PATH    = -10;

	private $settings;

	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$result         = parent::exec("describe site", true);
		$this->table = ($result === false) ? 'settings' : 'site';
		$this->setCovers();

		return $this->pdo;
	}

	/**
	 * Non-existent variables are assumed to be simple Settings.
	 *
	 * @param $name
	 *
	 * @return string
	 */
	public function __get($name)
	{
		return $this->getSetting($name);
	}

	/**
	 * Retrieve one or all settings from the Db as a string or an array;
	 *
	 * @param array|string $options Name of setting to retrieve (null for all settings)
	 *                              or array of 'feature', 'section', 'name' of setting{s} to retrieve
	 *
	 * @return string|array|bool
	 */
	public function getSetting($options = [])
	{
		// todo: think about making this static so it can be accessed without instantiating.
		if (!is_array($options)) {
			$options = $this->_dottedToArray($options);
			if (isset($options['setting']) && isset($this->settings[$options['setting']])) {
				return $this->settings[$options['setting']];
			}
		}

		$defaults = [
			'section'    => '',
			'subsection' => '',
			'name'       => null,
		];
		$options += $defaults;

		if ($this->table == 'settings') {
			$result = $this->_getFromSettings($options);
		} else {
			$result = $this->_getFromSites($options);
		}
		return $result;
	}

	public function rowToArray(array $row)
	{
		$this->settings[$row['setting']] = $row['value'];
	}

	public function rowsToArray(array $rows)
	{
		foreach($rows as $row) {
			if (is_array($row)) {
				$this->rowToArray($row);
			}
		}
		return $this->settings;
	}

	public function setCovers()
	{
		$path = $this->getSetting([
				'section' 		=> 'site',
				'subsection'	=> 'main',
				'name' 			=> 'coverspath',
				'setting' 		=> 'coverspath',
			]);
		Utility::setCoversConstant($path);
	}

	/**
	 * Set a setting in the database.
	 *
	 * @param array $options Array containing the mandatory keys of 'section', 'subsection', and 'value'
	 *
	 * @return boolean	true or false indicating success/failure.
	 */
	public function setSetting(array $options)
	{
		if (count($options) == 1) {
			foreach ($options as $key => $value) {
				$options = $this->_dottedToArray($key);
				$options['value'] = $value;
			}
		}

		$result = false;
		$defaults = [
			'section'    => null,
			'subsection' => null,
			'name'       => '',
			'value'      => null,
			'setting'    => null,
		];
		$options += $defaults;

		$temp1 = $options['section'] . $options['subsection'] . $options['name'];
		$temp2 = $options['section'] . $options['subsection'] . $options['setting'];
		if (!empty($temp1) || !empty($temp2)) {
			if (empty($temp1)) {
				if (isset($this->settings[$options['setting']])) {
					$this->settings[$options['setting']] = $options['value'];
				}
				$result = $this->update($options);
			} else if (!empty($options['name'])) {
				$where = sprintf("name = '%s'", $options['name']);
				$where .= ($options['section'] === null) ? '' : sprintf(" AND section = '%s'", $options['section']);
				$where .= ($options['subsection'] === null) ? '' : sprintf(" AND subsection = '%s'", $options['subsection']);

				$sql    = sprintf("UPDATE settings SET value = '%s' WHERE %s",
								  $options['value'],
								  $where);
				$result = $this->pdo->query($sql);
			}
		}

		return ($result === false) ? false : true;
	}

	public function table()
	{
		return $this->table;
	}

	public function update($form)
	{
		$error = $this->_validate($form);

		if ($error === null) {
			$sql = $sqlKeys = [];
			foreach ($form as $settingK => $settingV) {
				$sql[]     = sprintf("WHEN %s THEN %s",
									 $this->escapeString($settingK),
									 $this->escapeString($settingV));
				$sqlKeys[] = $this->escapeString($settingK);
			}

			$table = $this->table();
			$this->queryExec(
				 sprintf("UPDATE $table SET value = CASE setting %s END WHERE setting IN (%s)",
						 implode(' ', $sql),
						 implode(', ', $sqlKeys)
				 )
			);
		} else {
			$form = $error;
		}
		return $form;
	}

	public function version()
	{
		try {
			$ver = (new Versions())->getTagVersion();
		} catch (\Exception $e) {
			$ver = '0.0.0';
		}
		return $ver;
	}

	protected function _dottedToArray($setting)
	{
		$result = [];
		if (is_string($setting)) {
			$parts = explode('.', $setting);
			switch (count($parts)) {
				case 3:
					list(
						$result['section'],
						$result['subsection'],
						$result['name'],
						) = $parts;
					break;
				case 2:
					list(
						$result['subsection'],
						$result['name'],
						) = $parts;
					break;
				case 1:
					list(
						$result['setting'],
						) = $parts;
					break;
			}
		} else {
			$result = false;
		}
		return $result;
	}

	protected function _getFromSettings($options)
	{
		$sql     = 'SELECT value FROM settings ';
		$where   = $options['section'] . $options['subsection'] . $options['name']; // Can't use expression in empty() < PHP 5.5
		if (!empty($where)) {
			$sql .= "WHERE section = '{$options['section']}' AND subsection = '{$options['subsection']}'";
			$sql .= empty($options['name']) ? '' : " AND name = '{$options['name']}'";
		} else {
			$sql .= "WHERE setting = '{$options['setting']}'";
		}
		$sql .= ' ORDER BY section, subsection, name';
		$result = $this->queryOneRow($sql);

		return isset($result['value']) ? $result['value'] : null;
	}

	protected function _getFromSites($options)
	{
		$setting = empty($options['setting']) ? $options['name'] : $options['setting'];
		$sql     = 'SELECT value FROM site ';
		if (!empty($setting)) {
			$sql .= "WHERE setting = '$setting'";
		}

		$result = $this->queryOneRow($sql);

		return $result['value'];
	}

	protected function _validate(array $fields)
	{
		ksort($fields);
		// Validate settings
		$fields['nzbpath'] = Utility::trailingSlash($fields['nzbpath']);
		$error             = null;
		switch (true) {
			case ($fields['mediainfopath'] != "" && !is_file($fields['mediainfopath'])):
				$error = Settings::ERR_BADMEDIAINFOPATH;
				break;
			case ($fields['ffmpegpath'] != "" && !is_file($fields['ffmpegpath'])):
				$error = Settings::ERR_BADFFMPEGPATH;
				break;
			case ($fields['unrarpath'] != "" && !is_file($fields['unrarpath'])):
				$error = Settings::ERR_BADUNRARPATH;
				break;
			case (empty($fields['nzbpath'])):
				$error = Settings::ERR_BADNZBPATH_UNSET;
				break;
			case (!file_exists($fields['nzbpath']) || !is_dir($fields['nzbpath'])):
				$error = Settings::ERR_BADNZBPATH;
				break;
			case (!is_readable($fields['nzbpath'])):
				$error = Settings::ERR_BADNZBPATH_UNREADABLE;
				break;
			case ($fields['checkpasswordedrar'] == 1 && !is_file($fields['unrarpath'])):
				$error = Settings::ERR_DEEPNOUNRAR;
				break;
			case ($fields['tmpunrarpath'] != "" && !file_exists($fields['tmpunrarpath'])):
				$error = Settings::ERR_BADTMPUNRARPATH;
				break;
			case ($fields['yydecoderpath'] != "" &&
				  $fields['yydecoderpath'] !== 'simple_php_yenc_decode' &&
				  !file_exists($fields['yydecoderpath'])):
				$error = Settings::ERR_BAD_YYDECODER_PATH;
		}

		return $error;
	}
}

/*
 * Putting procedural stuff inside class scripts like this is BAD. Do not use this as an excuse to do more.
 * This is a temporary measure until a proper frontend for cli stuff can be implemented with li3.
 */
if (Utility::isCLI() && isset($argv[1])) {
	echo (new Settings())->getSetting($argv[1]);
}
