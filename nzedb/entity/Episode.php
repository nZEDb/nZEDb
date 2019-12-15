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
 * @author    niel
 * @copyright 2019 nZEDb
 */

namespace nzedb\entity;


use CanIHaveSomeCoffee\TheTVDbAPI\Model\BasicEpisode;

class Episode extends \CanIHaveSomeCoffee\TheTVDbAPI\Model\BasicEpisode
{
	protected $basicFields = [
		'absoluteNumber',
		'airedEpisodeNumber',
		'airedSeason',
		'dvdEpisodeNumber',
		'dvdSeason',
		'episodeName',
		'firstAired',
		'id',
		'lastUpdated',
		'overview'
	];

	public function __construct(BasicEpisode $episode)
	{
		foreach ($basicFields as $field => $value) {
			$this->$field = $episode->$field;
		}
	}

	public function __get($name)
	{
		$alias = $this->aliasFields($name);
		if ($alias !== null) {
			return $this->$alias;
		}
	}

	public function __set($name, $value)
	{
		$alias = $this->aliasFields($name);
		if ($alias !== null) {
			$this->$alias = $value;
		}
	}

	public function complete_SE()
	{
		return \sprintf('S%02dE%02d)', $this->getSeriesNo(), $this->getEpisodeNo());
	}

	/**
	 * @return int|null
	 */
	public function getEpisodeNo(): ?int
	{
		return $this->airedEpisodeNumber;
	}

	/**
	 * @return string|null
	 */
	public function getFirstAired(): ?string
	{
		return $this->firstAired;
	}

	/**
	 * @return string|null
	 */
	public function getTitle(): ?string
	{
		return $this->episodeName;
	}

	/**
	 * @return int|null
	 */
	public function getSeriesNo(): ?int
	{
		return $this->airedSeason;
	}

	/**
	 * @return mixed
	 */
	public function getSummary(): ?string
	{
		return $this->summary;
	}

	/**
	 * @param int|null $value
	 */
	public function setEpisodeNo(int $value): void
	{
		$this->airedEpisodeNumber = $value;
	}

	/**
	 * @param string|null $value
	 */
	public function setFirstaired(?string $value): void
	{
		$this->firstAired = $value;
	}

	/**
	 * @param int|null $value
	 */
	public function setSeriesNo(?int $value): void
	{
		$this->airedSeason = $value;
	}

	/**
	 * @param string|null $value
	 */
	public function setSummary(?string $value): void
	{
		$this->overview = $value;
	}

	/**
	 * @param string|null $value
	 */
	public function setTitle($value): void
	{
		$this->episodeName = $value;
	}

	/**
	 * Returns the fields as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'episode'     => $this->airedEpisodeNumber,
			'firstaired'  => $this->firstAired,
			'se_complete' => $this->complete_SE(),
			'series'      => $this->airedSeason,
			'summary'     => $this->overview,
			'title'       => $this->episodeName,
		];
	}

	protected function aliasFields($name)
	{
		switch ($name) {
			case 'episodeNo':
				$alias = 'airedEpisodeNumber';
				break;
			case 'seriesNo':
				$alias = 'airedSeason';
				break;
			case 'summary':
				$alias = 'overview';
				break;
			case 'title':
				$alias = 'episodeName';
				break;
			default:
				$alias = null;
		}

		return $alias;
	}
}

