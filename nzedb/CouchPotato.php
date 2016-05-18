<?php
namespace nzedb;

use nzedb\utility\Misc;

/**
 * Class SABnzbd
 */
class CouchPotato
{
	/**
	 * URL to the CP server.
	 * @var string|array|bool
	 */
	public $cpurl = '';

	/**
	 * The CP key.
	 * @var string|array|bool
	 */
	public $cpapi = '';

	/**
	 * Imdb ID
	 * @var string
	 */
	public $imdbid = '';

	/**
	 * Construct.
	 *
	 * @param \BasePage $page
	 */
	public function __construct(&$page)
	{
		$this->cpurl = !empty($page->userdata['cp_url']) ? $page->userdata['cp_url'] : '';
		$this->cpapi = !empty($page->userdata['cp_api']) ? $page->userdata['cp_api'] : '';
	}

	/**
	 * Send a movie to CouchPotato.
	 *
	 * @param string $id
	 * @return bool|mixed
	 */
	public function sendToCouchPotato($id)
	{
		if (strlen($id) == 40) {
			$relData = (new Releases())->getByGuid($id);
			$this->imdbid = $relData['imdbid'];
		} else {
			$this->imdbid = $id;
		}

		return Misc::getUrl([
				'url' => $this->cpurl .
					'/api/' .
					$this->cpapi .
					'/movie.add/?identifier=tt' .
					$this->imdbid,
				'verifypeer' => false,
			]
		);
	}
}
