<?php
namespace nzedb;

use app\models\SteamApps;
use b3rs3rk\steamfront\Main;
use nzedb\utility\Misc;

class Steam
{

	/**
	 * @var
	 */
	public $searchTerm;


	/**
	 * @var
	 */
	protected $steamGameID;

	public function __construct()
	{
		$this->steamClient = new Main(
			[
				'country_code' => 'us',
				'local_lang' => 'english'
			]
		);
	}


	/**
	 * Gets all Information for the game.
	 *
	 * @param integer $appID
	 *
	 * @return array|bool
	 */
	public function getAll($appID)
	{
		$res = $this->steamClient->getAppDetails($appID);

		if ($res !== null) {
			$result = [
				'title'       => $res->name,
				'description' => $res->description['short'],
				'cover'       => $res->images['header'],
				'backdrop'    => $res->images['background'],
				'steamid'     => $res->appid,
				'directurl'   => Main::STEAM_STORE_ROOT . 'app/' . $res->appid,
				'publisher'   => $res->publishers,
				'rating'      => $res->metacritic['score'],
				'releasedate' => $res->releasedate['date'],
				'genres'      => implode(',', array_column($res->genres, 'description'))
			];

			return $result;
		}

		return false;
	}

	/**
	 * Searches for a game for a 90% match
	 *
	 * @param string $searchTerm
	 *
	 * @return bool
	 */
	public function search($searchTerm)
	{
		if (empty($searchTerm)) {
			return false;
		}

		$steamGames = SteamApps::find('all',
			['fields' => ['name', 'appid'] ,
			 'order' => ['name' => 'ASC']
			]);

		foreach ($steamGames as $gamesArray) {
			similar_text(strtolower($gamesArray->name), strtolower($searchTerm), $percent);
			if ($percent > 90) {
				return $gamesArray->appid;
			}
		}
		return false;
	}

	public function populateSteamAppsTable()
	{
		$fullAppArray = $this->steamClient->getFullAppList();
		$inserted = $dupe = 0;
		echo 'Populating steam apps table' . PHP_EOL;
		foreach ($fullAppArray as $appsArray) {
			foreach ($appsArray as $appArray) {
				foreach ($appArray as $app) {
					$dupeCheck = SteamApps::find('first',
						[
							'conditions' =>
								[
									'name' => $app['name']
								],
							'fields'     => ['name'],
							'limit'      => 1,
						]
					);


					if ($dupeCheck === null) {
						$steamApps = SteamApps::create([
								'appid' => $app['appid'],
								'name'  => $app['name'],
							]
						);
						$steamApps->save();
						$inserted++;
						if ($inserted % 500 == 0) {
							echo PHP_EOL . number_format($inserted) . ' apps inserted.' . PHP_EOL;
						} else {
							echo '.';
						}
					} else {
						$dupe++;
					}
				}
			}
		}
		echo PHP_EOL . 'Added ' . $inserted . ' new steam apps, '. $dupe . ' duplicates skipped' . PHP_EOL;
	}
}
