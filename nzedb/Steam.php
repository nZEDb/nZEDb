<?php
namespace nzedb;

use app\models\SteamApps;
use b3rs3rk\steamfront\Main;
use nzedb\db\DB;

class Steam
{
	const STEAM_MATCH_PERCENTAGE = 90;

	/**
	 * @var string The parsed game name from searchname
	 */
	public $searchTerm;

	/**
	 * @var int The ID of the Steam Game matched
	 */
	protected $steamGameID;

	/**
	 * @var DB
	 */
	protected $pdo;

	/**
	 * Steam constructor.
	 *
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		$defaults = ['DB' => null];
		$options += $defaults;

		$this->pdo = ($options['DB'] instanceof DB ? $options['DB'] : new DB());

		$this->steamClient = new Main(
			[
				'country_code' => 'us',
				'local_lang'   => 'english'
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
	 * Searches Steam Apps table for best title match -- prefers 100% match but returns highest over 90%
	 *
	 * @param string $searchTerm The parsed game name from the release searchname
	 *
	 * @return false|int $bestMatch The Best match from the given search term
	 */
	public function search($searchTerm)
	{
		$bestMatch = false;

		if (empty($searchTerm)) {

			return $bestMatch;
		}

		$results = $this->pdo->queryDirect("
			SELECT name, appid
			FROM steam_apps
			WHERE MATCH(name) AGAINST({$this->pdo->escapeString($searchTerm)})
			LIMIT 20"
		);

		if ($results instanceof \Traversable) {
			$bestMatchPct = 0;
			foreach ($results as $result) {
				// If we have an exact string match set best match and break out
				if ($result['name'] === $searchTerm) {
					$bestMatch = $result['appid'];
					break;
				} else {
					similar_text(strtolower($result['name']), strtolower($searchTerm), $percent);
					// If similartext reports an exact match set best match and break out
					if ($percent == 100) {
						$bestMatch = $result['appid'];
						break;
					} else if ($percent >= self::STEAM_MATCH_PERCENTAGE) {
						if ($percent > $bestMatchPct) {
							$bestMatch = $result['appid'];
							$bestMatchPct = $percent;
						}
					}
				}
			}
		}

		return $bestMatch;
	}

	/**
	 * Downloads full Steam Store dump and imports data into local data
	 */
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
						$steamApps = SteamApps::create(
							[
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
