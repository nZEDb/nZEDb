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
 * @copyright 2019 nZEDb
 */

namespace nzedb;

/**
 * Representation of an IMDb entry
 *
 * Currently only the ID is handled.
 *
 * @package nzedb
 */
class Imdb
{
	public const MAX_DIGITS = 8;

	private $id = null;

	public function __construct(int $id = null)
	{
		if ($id !== null) {
			$this->id = $id;
		}
	}

	/**
	 * Returns a string version of the IMDb id value.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return (string)$this->getIdPadded();
	}

	/**
	 * @return int|null
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * Returns the IMDb id padded with leading zeroes.
	 *
	 * @return string
	 */
	public function getIdPadded(): string
	{
		return (string)\str_pad($this->id, self::MAX_DIGITS, '0', \STR_PAD_LEFT);
	}

	public function getIdShortPadded(): string
	{
		return (string)\str_pad($this->id, 7, '0', \STR_PAD_LEFT);
	}

	/**
	 * Returns the id formatted as IMDb does (padded to MAX_DIGITS length with leading zeros and
	 * prefixed with 'tt'.
	 *
	 * @return string
	 */
	public function getIMDbFormat(): string
	{
		return 'tt' . $this->getIdPadded();
	}

	/**
	 * @param int $id Value to set $this->id to.
	 *
	 * @return void
	 */
	public function setId(int $id ): void
	{
		$this->id = $id;
	}
}
