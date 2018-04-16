<?php
namespace nzedb;

use nzedb\db\DB;

class Category
{
	/**
	 * Category constants.
	 * Do NOT use the values, as they may change, always use the constant - that's what it's for.
	 */
	const BOOKS_COMICS = '7030';
	const BOOKS_EBOOK = '7020';
	const BOOKS_FOREIGN = '7060';
	const BOOKS_MAGAZINES = '7010';
	const BOOKS_ROOT = '7000';
	const BOOKS_TECHNICAL = '7040';
	const BOOKS_UNKNOWN = '7999';
	const GAME_3DS = '1110';
	const GAME_NDS = '1010';
	const GAME_OTHER = '1999';
	const GAME_PS3 = '1080';
	const GAME_PS4 = '1180';
	const GAME_PSP = '1020';
	const GAME_PSVITA = '1120';
	const GAME_ROOT = '1000';
	const GAME_WII = '1030';
	const GAME_WIIU = '1130';
	const GAME_WIIWARE = '1060';
	const GAME_XBOX = '1040';
	const GAME_XBOX360 = '1050';
	const GAME_XBOX360DLC = '1070';
	const GAME_XBOXONE = '1140';
	const MOVIE_3D = '2050';
	const MOVIE_BLURAY = '2060';
	const MOVIE_DVD = '2070';
	const MOVIE_FOREIGN = '2010';
	const MOVIE_HD = '2040';
	const MOVIE_UHD = '2045';
	const MOVIE_OTHER = '2999';
	const MOVIE_ROOT = '2000';
	const MOVIE_SD = '2030';
	const MOVIE_WEBDL = '2080';
	const MUSIC_AUDIOBOOK = '3030';
	const MUSIC_FOREIGN = '3060';
	const MUSIC_LOSSLESS = '3040';
	const MUSIC_MP3 = '3010';
	const MUSIC_OTHER = '3999';
	const MUSIC_ROOT = '3000';
	const MUSIC_VIDEO = '3020';
	const OTHER_HASHED = '0020';
	const OTHER_MISC = '0010';
	const OTHER_ROOT = '0000';
	const PC_0DAY = '4010';
	const PC_GAMES = '4050';
	const PC_ISO = '4020';
	const PC_MAC = '4030';
	const PC_PHONE_ANDROID = '4070';
	const PC_PHONE_IOS = '4060';
	const PC_PHONE_OTHER = '4040';
	const PC_ROOT = '4000';
	const TV_ANIME = '5070';
	const TV_DOCUMENTARY = '5080';
	const TV_FOREIGN = '5020';
	const TV_HD = '5040';
	const TV_UHD = '5045';
	const TV_OTHER = '5999';
	const TV_ROOT = '5000';
	const TV_SD = '5030';
	const TV_SPORT = '5060';
	const TV_WEBDL = '5010';
	const XXX_DVD = '6010';
	const XXX_IMAGESET = '6060';
	const XXX_OTHER = '6999';
	const XXX_PACKS = '6070';
	const XXX_ROOT = '6000';
	const XXX_SD = '6080';
	const XXX_WEBDL = '6090';
	const XXX_WMV = '6020';
	const XXX_X264 = '6040';
	const XXX_UHD = '6045';
	const XXX_XVID = '6030';

	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_DISABLED = 2;

	/**
	 * @var DB
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

		$this->pdo = ($options['Settings'] instanceof DB ? $options['Settings'] : new DB());
	}

	/**
	 * Get array of categories in DB.
	 *
	 * @param bool  $activeonly
	 * @param array $excludedcats
	 *
	 * @return array
	 */
	public function getCategories($activeonly = false, $excludedcats = [])
	{
		return $this->pdo->query(
			"SELECT c.id, CONCAT(cp.title, ' > ',c.title) AS title, cp.id AS parentid, c.status, c.minsize
			FROM categories c
			INNER JOIN categories cp ON cp.id = c.parentid " .
			($activeonly ?
				sprintf(
					" WHERE c.status = %d %s ",
					Category::STATUS_ACTIVE,
					(count($excludedcats) > 0 ? " AND c.id NOT IN (" . implode(",", $excludedcats) . ")" : '')
				) : ''
			) .
			" ORDER BY c.id"
		);
	}

	/**
	 * Parse category search constraints
	 *
	 * @param array $cat
	 *
	 * @return string $catsrch
	 */
	public function getCategorySearch(array $cat = [])
	{
		$categories = [];

		// If multiple categories were sent in a single array position, slice and add them
		if (strpos($cat[0], ',') !== false) {
			$tmpcats = explode(',', $cat[0]);
			// Reset the category to the first comma separated value in the string
			$cat[0] = $tmpcats[0];
			// Add the remaining categories in the string to the original array
			foreach (array_slice($tmpcats, 1) AS $tmpcat) {
				$cat[] = $tmpcat;
			}
		}

		foreach ($cat as $category) {
			if ($category != -1 && $this->isParent($category)) {
				foreach ($this->getChildren($category) as $child) {
					$categories[] = $child['id'];
				}
			} else if ($category > 0) {
				$categories[] = $category;
			}
		}

		$catCount = count($categories);

		switch ($catCount) {
			//No category constraint
			case 0:
				$catsrch = ' AND 1=1 ';
				break;
			// One category constraint
			case 1:
				$catsrch = " AND r.categories_id = {$categories[0]}";
				break;
			// Multiple category constraints
			default:
				$catsrch = " AND r.categories_id IN (" . implode(", ", $categories) . ") ";
				break;
		}

		return $catsrch;
	}

	/**
	 * Returns a concatenated list of other categories
	 *
	 * @return string
	 */
	public static function getCategoryOthersGroup()
	{
		return implode(",",
			[
				self::BOOKS_UNKNOWN,
				self::GAME_OTHER,
				self::MOVIE_OTHER,
				self::MUSIC_OTHER,
				self::PC_PHONE_OTHER,
				self::TV_OTHER,
				self::OTHER_HASHED,
				self::XXX_OTHER,
				self::OTHER_MISC,
				self::OTHER_HASHED
			]
		);
	}

	public static function getCategoryValue($category)
	{
		return constant('self::' . $category);
	}

	/**
	 * Check if category is parent.
	 *
	 * @param $cid
	 *
	 * @return bool
	 */
	public function isParent($cid)
	{
		$ret = $this->pdo->query(
			sprintf("SELECT id FROM categories WHERE id = %d AND parentid IS NULL", $cid),
			true, nZEDb_CACHE_EXPIRY_LONG
		);
		return (isset($ret[0]['id']));
	}

	/**
	 * @param bool $activeonly
	 *
	 * @return array
	 */
	public function getFlat($activeonly = false)
	{
		$act = "";
		if ($activeonly) {
			$act = sprintf(" WHERE c.status = %d ", Category::STATUS_ACTIVE);
		}
		return $this->pdo->query("SELECT c.*, (SELECT title FROM categories WHERE id=c.parentid) AS parentName FROM categories c " . $act . " ORDER BY c.id");
	}

	/**
	 * Get children of a parent category.
	 *
	 * @param $cid
	 *
	 * @return array
	 */
	public function getChildren($cid)
	{
		return $this->pdo->query(
			sprintf("SELECT c.* FROM categories c WHERE parentid = %d", $cid),
			true, nZEDb_CACHE_EXPIRY_LONG
		);
	}

	/**
	 * Get names of enabled parent categories.
	 * @return array
	 */
	public function getEnabledParentNames()
	{
		return $this->pdo->query(
			"SELECT title FROM categories WHERE parentid IS NULL AND status = 1",
			true, nZEDb_CACHE_EXPIRY_LONG
		);
	}

	/**
	 * Returns category ID's for site disabled categories.
	 *
	 * @return array
	 */
	public function getDisabledIDs()
	{
		return $this->pdo->query(
			"SELECT id FROM categories WHERE status = 2 OR parentid IN (SELECT id FROM categories WHERE status = 2 AND parentid IS NULL)",
			true, nZEDb_CACHE_EXPIRY_LONG
		);
	}

	/**
	 * Get a single category by id.
	 *
	 * @param string|int $id
	 *
	 * @return array|bool
	 */
	public function getById($id)
	{
		return $this->pdo->queryOneRow(
			sprintf(
				"SELECT c.disablepreview, c.id,
					CONCAT(COALESCE(cp.title,'') ,
					CASE WHEN cp.title IS NULL THEN '' ELSE ' > ' END , c.title) AS title,
					c.status, c.parentID, c.minsize
				FROM categories c
				LEFT OUTER JOIN categories cp ON cp.id = c.parentid
				WHERE c.id = %d", $id
			)
		);
	}

	/**
	 * Get multiple categories.
	 *
	 * @param array $ids
	 *
	 * @return array|bool
	 */
	public function getByIds($ids)
	{
		if (count($ids) > 0) {
			return $this->pdo->query(
				sprintf(
					"SELECT CONCAT(cp.title, ' > ',c.title) AS title
					FROM categories c
					INNER JOIN categories cp ON cp.id = c.parentid
					WHERE c.id IN (%s)", implode(',', $ids)
				), true, nZEDb_CACHE_EXPIRY_LONG
			);
		} else {
			return false;
		}
	}

	/**
	 * Update a category.
	 * @param $id
	 * @param $status
	 * @param $desc
	 * @param $disablepreview
	 * @param $minsize
	 *
	 * @return bool
	 */
	public function update($id, $status, $desc, $disablepreview, $minsize)
	{
		return $this->pdo->queryExec(
			sprintf(
				"UPDATE categories SET disablepreview = %d, status = %d, description = %s, minsize = %d
				WHERE id = %d",
				$disablepreview, $status, $this->pdo->escapeString($desc), $minsize, $id
			)
		);
	}

	/**
	 * @param array $excludedcats
	 *
	 * @return array
	 */
	public function getForMenu($excludedcats = [], $roleexcludedcats = [])
	{
		$ret = [];

		$exccatlist = '';
		if (count($excludedcats) > 0 && count($roleexcludedcats) == 0) {
			$exccatlist = ' AND id NOT IN (' . implode(',', $excludedcats) . ')';
		} elseif (count($excludedcats) > 0 && count($roleexcludedcats) > 0) {
			$exccatlist = ' AND id NOT IN (' . implode(',', $excludedcats) . ',' . implode(',', $roleexcludedcats) . ')';
		} elseif (count($excludedcats) == 0 && count($roleexcludedcats) > 0) {
			$exccatlist = ' AND id NOT IN (' . implode(',', $roleexcludedcats) . ')';
		}

		$arr = $this->pdo->query(
			sprintf('SELECT * FROM categories WHERE status = %d %s', Category::STATUS_ACTIVE, $exccatlist),
			true, nZEDb_CACHE_EXPIRY_LONG
		);

		foreach($arr as $key => $val) {
			if($val['id'] == '0') {
				$item = $arr[$key];
				unset($arr[$key]);
				array_push($arr, $item);
				break;
			}
		}

		foreach ($arr as $a) {
			if (empty($a['parentid'])) {
				$ret[] = $a;
			}
		}

		foreach ($ret as $key => $parent) {
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
				$ret[$key]['subcatlist'] = $subcatlist;
			} else {
				unset($ret[$key]);
			}
		}
		return $ret;
	}

	/**
	 * @param bool $blnIncludeNoneSelected
	 *
	 * @return array
	 */
	public function getForSelect($blnIncludeNoneSelected = true)
	{
		$categories = $this->getCategories();
		$temp_array = [];

		if ($blnIncludeNoneSelected) {
			$temp_array[-1] = "--Please Select--";
		}

		foreach ($categories as $category) {
			$temp_array[$category["id"]] = $category["title"];
		}

		return $temp_array;
	}

	/**
	 * Return the parent and category name from the supplied categoryID.
	 * @param $ID
	 *
	 * @return string
	 */
	public function getNameByID($ID)
	{
		$cat = $this->pdo->queryOneRow(
			sprintf("
				SELECT c.title AS ctitle, cp.title AS ptitle
				FROM categories c
				INNER JOIN categories cp ON c.parentid = cp.id
				WHERE c.id = %d",
				$ID
			)
		);
		return $cat["ptitle"] . "->" . $cat["ctitle"];
	}
}
