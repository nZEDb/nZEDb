<?php
namespace nzedb;

use app\models\SteamApps;
use app\models\Settings;
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
	 * @var
	 */
	protected $lastUpdate;

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

		$this->steamFront = new Main(
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
		$res = $this->steamFront->getAppDetails($appID);

		if ($res !== false) {
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

		if ($res === false) {
			ColorCLI::doEcho(ColorCLI::notice('Steam did not return game data'));
		}

		return false;
	}

	/**
	 * Searches Steam Apps table for best title match -- prefers 100% match but returns highest over 90%
	 *
	 * @param string $searchTerm The parsed game name from the release searchname
	 *
	 * @return false|int $bestMatch The Best match from the given search term
	 * @throws \Exception
	 */
	public function search($searchTerm)
	{
		$bestMatch = false;

		if (empty($searchTerm)) {
			ColorCLI::doEcho(ColorCLI::notice('Search term cannot be empty'));

			return $bestMatch;
		}

		$this->populateSteamAppsTable();

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
				}

				similar_text(strtolower($result['name']), strtolower($searchTerm), $percent);
				// If similar_text reports an exact match set best match and break out
				if ($percent === 100) {
					$bestMatch = $result['appid'];
					break;
				}
				if ($percent >= self::STEAM_MATCH_PERCENTAGE && $percent > $bestMatchPct) {
					$bestMatch = $result['appid'];
					$bestMatchPct = $percent;
				}
			}
		}
		if ($bestMatch === false) {
			ColorCLI::doEcho(ColorCLI::notice('Steam search returned no valid results'));
		}

		return $bestMatch;
	}

	/**
	 * Downloads full Steam Store dump and imports data into local table
	 * @throws \Exception
	 */
	public function populateSteamAppsTable()
	{
		$lastUpdate = Settings::value('APIs.steam_apps.last_update');
		$this->lastUpdate = $lastUpdate > 0 ? $lastUpdate : 0;
		if ((time() - (int)$this->lastUpdate) > 86400) {
			// Set time we updated steam_apps table
			$this->setLastUpdated();
			$fullAppArray = $this->steamFront->getFullAppList();
			$inserted = $dupe = 0;
			echo ColorCLI::info('Populating steam apps table') . PHP_EOL;
			foreach ($fullAppArray as $appsArray) {
				foreach ($appsArray as $appArray) {
					foreach ($appArray as $app) {
						$dupeCheck = SteamApps::find('first',
							[
								'conditions' =>
									[
										'name'  => $app['name'],
										'appid' => $app['appid'],
									],
								'fields'     => ['appid'],
								'limit'      => 1,
							]
						);

						if ($dupeCheck === null) {
							$this->pdo->queryExec(sprintf('INSERT IGNORE INTO steam_apps (name, appid) VALUES (%s, %d)', $this->pdo->escapeString($app['name']), $app['appid']));
							$inserted++;
							if ($inserted % 500 === 0) {
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
			echo PHP_EOL . 'Added ' . $inserted . ' new steam apps, ' . $dupe . ' duplicates skipped' . PHP_EOL;
		}
	}

	/**
	 * Sets the database time for last full AniDB update
	 */
	private function setLastUpdated()
	{
		Settings::update(
			['value' => time()],
			['section' => 'APIs', 'subsection' => 'steam_apps', 'name' => 'last_update']
		);
	}
}
