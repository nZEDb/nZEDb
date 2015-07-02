<?php
namespace nzedb;

use nzedb\db\Settings;
use nzedb\utility\Versions;

class Caps
{
	const CAT_GAME_NDS = 1010;
	const CAT_GAME_PSP = 1020;
	const CAT_GAME_WII = 1030;
	const CAT_GAME_XBOX = 1040;
	const CAT_GAME_XBOX360 = 1050;
	const CAT_GAME_WIIWARE = 1060;
	const CAT_GAME_XBOX360DLC = 1070;
	const CAT_GAME_PS3 = 1080;
	const CAT_GAME_OTHER = 1090;
	const CAT_GAME_3DS = 1110;
	const CAT_GAME_PSVITA = 1120;
	const CAT_GAME_WIIU = 1130;
	const CAT_GAME_XBOXONE = 1140;
	const CAT_GAME_PS4 = 1180;
	const CAT_MOVIE_FOREIGN = 2010;
	const CAT_MOVIE_OTHER = 2020;
	const CAT_MOVIE_SD = 2030;
	const CAT_MOVIE_HD = 2040;
	const CAT_MOVIE_3D = 2050;
	const CAT_MOVIE_BLURAY = 2060;
	const CAT_MOVIE_DVD = 2070;
	const CAT_MOVIE_WEBDL = 2080;
	const CAT_MUSIC_MP3 = 3010;
	const CAT_MUSIC_VIDEO = 3020;
	const CAT_MUSIC_AUDIOBOOK = 3030;
	const CAT_MUSIC_LOSSLESS = 3040;
	const CAT_MUSIC_OTHER = 3050;
	const CAT_MUSIC_FOREIGN = 3060;
	const CAT_PC_0DAY = 4010;
	const CAT_PC_ISO = 4020;
	const CAT_PC_MAC = 4030;
	const CAT_PC_PHONE_OTHER = 4040;
	const CAT_PC_GAMES = 4050;
	const CAT_PC_PHONE_IOS = 4060;
	const CAT_PC_PHONE_ANDROID = 4070;
	const CAT_TV_WEBDL = 5010;
	const CAT_TV_FOREIGN = 5020;
	const CAT_TV_SD = 5030;
	const CAT_TV_HD = 5040;
	const CAT_TV_OTHER = 5050;
	const CAT_TV_SPORT = 5060;
	const CAT_TV_ANIME = 5070;
	const CAT_TV_DOCUMENTARY = 5080;
	const CAT_XXX_DVD = 6010;
	const CAT_XXX_WMV = 6020;
	const CAT_XXX_XVID = 6030;
	const CAT_XXX_X264 = 6040;
	const CAT_XXX_OTHER = 6050;
	const CAT_XXX_IMAGESET = 6060;
	const CAT_XXX_PACKS = 6070;
	const CAT_XXX_SD = 6080;
	const CAT_XXX_WEBDL = 6090;
	const CAT_MISC = 7010;
	const CAT_OTHER_HASHED = 7020;
	const CAT_BOOKS_EBOOK = 8010;
	const CAT_BOOKS_COMICS = 8020;
	const CAT_BOOKS_MAGAZINES = 8030;
	const CAT_BOOKS_TECHNICAL = 8040;
	const CAT_BOOKS_OTHER = 8050;
	const CAT_BOOKS_FOREIGN = 8060;
	const CAT_PARENT_GAME = 1000;
	const CAT_PARENT_MOVIE = 2000;
	const CAT_PARENT_MUSIC = 3000;
	const CAT_PARENT_PC = 4000;
	const CAT_PARENT_TV = 5000;
	const CAT_PARENT_XXX = 6000;
	const CAT_PARENT_MISC = 7000;
	const CAT_PARENT_BOOKS = 8000;
	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_DISABLED = 2;

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
	public function getForMenu($excludedcats = [])
	{

		$ret = array();

		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND id NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$arr = $this->pdo->query(sprintf('SELECT * FROM category WHERE status = %d %s', Category::STATUS_ACTIVE, $exccatlist));
		foreach ($arr as $a) {
			if ($a['parentid'] == '') {
				$cats[] = $a;
			}
		}

		foreach ($cats as $key => $parent) {
			$subcatlist = [];
			$subcatnames = [];
			foreach ($arr as $a) {
				if ($a['parentid'] == $parent['id']) {
					$subcatlist[] = $a;
					$subcatnames[] = $a['title'];
				}
			}

			if (count($subcatlist) > 0) {
				array_multisort($subcatnames, SORT_ASC, $subcatlist);
				$cats[$key]['subcatlist'] = $subcatlist;
			} else {
				unset($cats[$key]);
			}
		}

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
		$ret["categories"] = $cats;

		return $ret;
	}
}
