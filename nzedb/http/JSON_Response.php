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
 * @author    ruhllatio
 * @copyright 2016 nZEDb
 */

namespace nzedb\http;

class JSON_Response
{
	public function __construct() {

		$this->returnColumns = [
			'title'         => 'searchname',
			'id'            => 'guid',
			'pubdate'       => 'adddate',
			'category_name' => 'category_name',
			'category'      => 'categories_id',
			'size'          => 'size',
			'files'         => 'totalpart',
			'poster'        => 'fromname',
			'tvtitle'       => 'tvtitle',
			'grabs'         => 'grabs',
			'comments'      => 'comments',
			'usenetdate'    => 'postdate',
			'group'         => 'group_name',
			''
		];
	}

	public function format($data)
	{
		$return = array();
		$i = 0;

		foreach ($data AS $datum) {
			foreach ($this->returnColumns AS $apicol => $dbcol) {
				if (isset($datum[$dbcol])) {
					$return[$i][$apicol] = $datum[$dbcol];
				}
			}
			$i++;
		}

		if (count($return) > 0) {
			return json_encode($return, JSON_PRETTY_PRINT);
		} else {
			return false;
		}
	}
}