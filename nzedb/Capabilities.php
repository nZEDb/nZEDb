<?php
namespace nzedb;

use nzedb\db\Settings;
use nzedb\utility\Versions;

class Capabilities
{
	/**
	 * @var Settings
	 */
	public $pdo;

	/**
	 * Construct.
	 *
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null,
		];
		$options += $defaults;
		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
	}

	/**
	 * @param array $excludedcats
	 *
	 * @return array
	 */
	public function getForMenu()
	{

		$ret = array();

		$serverroot = "";
		$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? true : false);

		if (isset($_SERVER['SERVER_NAME'])) {
			$serverroot = (
				($https === true ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] .
				(($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') ? ':' . $_SERVER['SERVER_PORT'] : '') .
				WWW_TOP . '/'
			);
		}

		$conf = array(
			"appversion" => (new Versions())->getTagVersion(),
			"version" => "0.1",
			"title" => $this->pdo->getSetting('title'),
			"strapline" => $this->pdo->getSetting('strapline'),
			"email" => $this->pdo->getSetting('email'),
			"url" => $serverroot,
			"image" => $serverroot . "themes_shared/images/logo.png"
		);

		$limit =array(
			"max" => 100,
			"default" => 100
		);

		$search = array(
			"search" => "yes",
			"tv-search" => "yes",
			"movie-search" => "yes",
			"audio-search" => "yes"
		);

		$registration = array(
			"available"=>"yes",
			"open"=> $this->pdo->getSetting('registerstatus')==0?"yes":"no"
		);

		$ret["server"] = $conf;
		$ret["limits"] = $limit;
		$ret["registration"] = $registration;
		$ret["searching"] = $search;

		return $ret;
	}
}
