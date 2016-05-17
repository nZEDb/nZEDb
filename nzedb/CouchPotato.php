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
	 * The SAB CP key.
	 * @var string|array|bool
	 */
	public $cpapi = '';

	/**
	 * ID of the current user
	 * @var string
	 */
	protected $uid = '';

	/**
	 * User's newznab API key
	 * @var string
	 */
	protected $rsstoken = '';

	/**
	 * nZEDb Site URL to send to SAB to download the NZB.
	 * @var string
	 */
	protected $serverurl = '';

	/**
	 * Construct.
	 *
	 * @param \BasePage $page
	 */
	public function __construct(&$page)
	{
		$this->uid = $page->userdata['id'];
		$this->rsstoken = $page->userdata['rsstoken'];
		$this->serverurl = $page->serverurl;
		$this->releases = new Releases();

		$this->cpurl = !empty($page->userdata['cp_url']) ? $page->userdata['cp_url'] : '';
		$this->cpapi = !empty($page->userdata['cp_api']) ? $page->userdata['cp_api'] : '';

	}

	/**
	 * Send a movie release to CouchPotato.
	 *
	 * @param int $imdbid The IMDB ID of the movie we want to send to couch
	 *
	 * @return bool|mixed
	 *
	 */
	public function sendToCouchPotato($imdbid)
	{
		return Misc::getUrl(
			[
				'url'        => $this->cpurl . '/api/' . $this->cpapi . '/movie.add/?identifier=tt' . $imdbid,
				'verifypeer' => false,
			]
		);
	}
}
