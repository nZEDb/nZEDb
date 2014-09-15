<?php

use nzedb\db\Settings;

class Category
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
	public function __construct(array $options = array())
	{
		$defaults = [
			'Settings' => null,
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
	}

	/**
	 * Get array of categories in DB.
	 *
	 * @param bool  $activeonly
	 * @param array $excludedcats
	 *
	 * @return array
	 */
	public function get($activeonly = false, $excludedcats = array())
	{
		return $this->pdo->query(
			"SELECT c.id, CONCAT(cp.title, ' > ',c.title) AS title, cp.id AS parentid, c.status, c.minsize
			FROM category c
			INNER JOIN category cp ON cp.id = c.parentid " .
			($activeonly ?
				sprintf(
					" WHERE c.status = %d %s ",
					\Category::STATUS_ACTIVE,
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
	public function getCategorySearch($cat = array())
	{
		$catsrch = ' (';

		foreach ($cat as $category) {

			$chlist = '-99';

			if ($category != -1 && $this->isParent($category)) {
				$children = $this->getChildren($category);

				foreach ($children as $child) {
					$chlist .= ', ' . $child['id'];
				}
			}

			if ($chlist != '-99') {
				$catsrch .= ' r.categoryid IN (' . $chlist . ') OR ';
			} else {
				$catsrch .= sprintf(' r.categoryid = %d OR ', $category);
			}
			$catsrch .= '1=2 )';
		}
		return $catsrch;
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
		$ret = $this->pdo->queryOneRow(sprintf("SELECT * FROM category WHERE id = %d AND parentid IS NULL", $cid));
		if ($ret) {
			return true;
		} else {
			return false;
		}
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
			$act = sprintf(" WHERE c.status = %d ", \Category::STATUS_ACTIVE);
		}
		return $this->pdo->query("SELECT c.*, (SELECT title FROM category WHERE id=c.parentid) AS parentName FROM category c " . $act . " ORDER BY c.id");
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
		return $this->pdo->query(sprintf("SELECT c.* FROM category c WHERE parentid = %d", $cid));
	}

	/**
	 * Get names of enabled parent categories.
	 * @return array
	 */
	public function getEnabledParentNames()
	{
		return $this->pdo->query("SELECT title FROM category WHERE parentid IS NULL AND status = 1");
	}

	/**
	 * Returns category ID's for site disabled categories.
	 *
	 * @return array
	 */
	public function getDisabledIDs()
	{
		return $this->pdo->query("SELECT id FROM category WHERE status = 2 OR parentid IN (SELECT id FROM category WHERE status = 2 AND parentid IS NULL)");
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
				FROM category c
				LEFT OUTER JOIN category cp ON cp.id = c.parentid
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
					FROM category c
					INNER JOIN category cp ON cp.id = c.parentid
					WHERE c.id IN (%s)", implode(',', $ids)
				)
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
				"UPDATE category SET disablepreview = %d, status = %d, description = %s, minsize = %d
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
	public function getForMenu($excludedcats = array())
	{
		$ret = array();

		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND id NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$arr = $this->pdo->query(sprintf('SELECT * FROM category WHERE status = %d %s', \Category::STATUS_ACTIVE, $exccatlist));
		foreach ($arr as $a) {
			if ($a['parentid'] == '') {
				$ret[] = $a;
			}
		}

		foreach ($ret as $key => $parent) {
			$subcatlist = array();
			$subcatnames = array();
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
		$categories = $this->get();
		$temp_array = array();

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
		$parent = $this->pdo->queryOneRow(sprintf("SELECT title FROM category WHERE id = %d", substr($ID, 0, 1) . "000"));
		$cat = $this->pdo->queryOneRow(sprintf("SELECT title FROM category WHERE id = %d", $ID));
		return $parent["title"] . " " . $cat["title"];
	}
}
