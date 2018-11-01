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
 * @copyright 2016 nZEDb
 */
namespace app\models;


class MultigroupPosters extends \app\extensions\data\Model
{
	protected $_meta = [
		'key' => ['poster']
	];

	public $validates = [
		'poster' => [
			'notEmpty',
			'message' => 'Empty poster value is not permitted.'
		]
	];

	public static function commaSeparatedList()
	{
		$list = [];
		$posters = MultigroupPosters::find('all',
			[
				'fields' => ['poster'],
				'order'  => ['poster' => 'ASC'],
			]
		);

		foreach ($posters as $poster) {
			$list[] = $poster->poster;
		}

		return implode("','", $list);
	}
}

?>
