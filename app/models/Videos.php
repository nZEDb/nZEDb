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
 * @copyright 2018 nZEDb
 */
namespace app\models;


class Videos extends \app\extensions\data\Model
{
	// Anime is one of the types below. _TV if serial, _FILM if cinema or OVA, etc.
	const TYPE_UNKNOWN = 0;
	const TYPE_TV = 1;		// TV programme, but not a film.
	const TYPE_FILM = 2;	// Film of any type, except if made for TV (i.e. TV Movie on IMDb)/
	const TYPE_TVFILM = 3;	// Made for TV Film

	public $validates = [];
}

?>
