<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2017 nZEDb
 */
namespace app\models;

class AnidbTitles extends \app\extensions\data\Model
{
	public $hasMany = [
		'AnidbEpisodes' => [
			'to'  => 'AnidbEpisodes',
			'key' => 'anidbid',
		],
		'AnidbInfo' => [
			'to'  => 'AnidbInfo',
			'key' => 'anidbid',
		]
	];

	public $_meta = [
		'connection' => 'default',
		'key'        => 'anidbid',
		'source'     => 'anidb_titles',
	];

	public $validates = [];

	public static function findRange($page = 1, $limit = ITEMS_PER_PAGE, $name = '')
	{
		$source = AnidbTitles::connection(); // Gives the connected data source i.e. a `Database` object.

		$where = ($name != '') ? " at.title LIKE '%$name%' AND" : '';
		$offset = $page > 1 ? ($page - 1) * $limit : '0';

		$sql = <<<QUERY
SELECT at.anidbid, GROUP_CONCAT(at.title SEPARATOR ', ') AS title
	FROM anidb_titles AS at
	WHERE $where at.lang = 'en'
	GROUP BY at.anidbid
	ORDER BY at.anidbid ASC
	LIMIT $offset, $limit;
QUERY;

		return $source->read($sql, ['return' => 'array']);
	}
}

?>
