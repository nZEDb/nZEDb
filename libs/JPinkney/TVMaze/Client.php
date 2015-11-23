<?php

namespace libs\JPinkney\TVMaze;

/**
 * Class Client
 *
 * @package libs\JPinkney\TVMaze
 */
class Client {

	CONST APIURL = 'http://api.tvmaze.com';

	/**
	 * @var array Embed Options for API Queries
	 */
	public $embed;

	/**
	 * @param array $options
	 */
	public function __construct($options = array())
	{
		;
	}

	/**
	 * Takes in a show name
	 * Outputs array of all the related shows for that given name
	 *
	 * @param $show_name
	 *
	 * @return array
	 */
	function search($show_name)
	{
		$relevant_shows = false;
		$url = self::APIURL . "/search/shows?q=" . rawurlencode($show_name);

		$shows = $this->getFile($url);

		if (is_array($shows)) {
			$relevant_shows = array();
			foreach ($shows as $series) {
				$TVShow = new TVShow($series['show']);
				array_push($relevant_shows, $TVShow);
			}
		}
		return $relevant_shows;
	}

	/**
	 * Takes in a show name with optional modifiers (akas)
	 * Outputs array of the MOST related show for that given name
	 *
	 * @param $show_name
	 *
	 * @return array
	 */
	function singleSearch($show_name)
	{
		$TVShow = false;
		$url = self::APIURL . "/singlesearch/shows?q=" . rawurlencode($show_name) . '&embed=akas';
		$shows = $this->getFile($url);

		if (is_array($shows)) {
			$TVShow = new TVShow($shows);
		}
		return array($TVShow);
	}

	/**
	 * Allows show lookup by using TVRage or TheTVDB ID
	 * site is the string of the website (either 'tvrage' or 'thetvdb') and the id is the id of the show on that respective site
	 *
	 * @param $site
	 * @param $ID
	 *
	 * @return TVShow
	 */
	function getShowBySiteID($site, $ID)
	{
		$site = strtolower($site);
		$url = self::APIURL . '/lookup/shows?' . $site . '=' . $ID;
		$show = $this->getFile($url);

		return new TVShow($show);
	}

	/**
	 * Takes in an actors name and outputs their actor object
	 *
	 * @param $name
	 *
	 * @return array
	 */
	function getPersonByName($name)
	{
		$name = strtolower($name);
		$url = self::APIURL . '/search/people?q=' . $name;
		$person = $this->getFile($url);

		$people = array();
		foreach($person as $peeps) {
			array_push($people, new Actor($peeps['person']));
		}

		return $people;
	}

	/**
	 * TODO: this still needs to be done
	 *
	 * @param null $country
	 * @param null $date
	 *
	 * @return array
	 */
	function getSchedule($country=null, $date=null) {
		if($country != null && $date != null) {
			$url = self::APIURL . '/schedule?country=' . $country .'&date='. $date;
		} else if ($country == null && $date != null) {
			$url = self::APIURL . '/schedule?date=' . $date;
		} else if ($country != null && $date == null) {
			$url = self::APIURL . '/schedule?country=' . $country;
		} else {
			$url = self::APIURL . '/schedule';
		}

		$schedule = $this->getFile($url);

		$show_list = array();
		foreach($schedule as $episode) {
			$ep = new Episode($episode);
			$show = new TVShow($episode['show']);
			array_push($show_list, $show, $ep);
		}

		return $show_list;
	}

	/**
	 * Takes in a show ID and outputs the TVShow Object
	 *
	 * @param      $ID
	 * @param null $embed_cast
	 *
	 * @return array|object
	 */
	function getShowByShowID($ID, $embed_cast = false)
	{
		if($embed_cast) {
			$url = self::APIURL . '/shows/'. $ID . '?embed=cast';
		} else {
			$url = self::APIURL . '/shows/' . $ID;
		}

		$show = $this->getFile($url);

		$cast = array();
		if ($embed_cast) {
			foreach($show['_embedded']['cast'] as $person) {
				$actor = new Actor($person['person']);
				$character = new Character($person['character']);
				array_push($cast, array($actor, $character));
			}
		}

		$TVShow = new TVShow($show);

		return ($embed_cast === true ? array($TVShow, $cast) : $TVShow);
	}

	/**
	 * Takes in a show ID and outputs the AKA Object
	 *
	 * @param      $ID
	 *
	 * @return array
	 */
	function getShowAKAs($ID)
	{
		$url = self::APIURL . '/shows/' . $ID . '/akas';

		$akas = $this->getFile($url);

		$AKA = new AKA($akas);

		if (!empty($akas['name'])) {

			return $AKA;
		}

		return false;
	}

	/**
	 * Takes in a show ID and outputs all the episode objects for that show in an array
	 *
	 * @param $ID
	 *
	 * @return array
	 */
	function getEpisodesByShowID($ID)
	{

		$url = self::APIURL . '/shows/' . $ID . '/episodes';

		$episodes = $this->getFile($url);

		$allEpisodes = array();
		if (is_array($episodes)) {
			foreach ($episodes as $episode) {
				$ep = new Episode($episode);
				array_push($allEpisodes, $ep);
			}
		}
		return $allEpisodes;
	}

	/**
	 * Returns a single episodes information by its show ID, season and episode numbers
	 *
	 * @param $ID
	 * @param $season
	 * @param $episode
	 *
	 * @return Episode|mixed
	 */
	function getEpisodeByNumber($ID, $season, $episode)
	{
		$ep = false;
		$url = self::APIURL . '/shows/' . $ID . '/episodebynumber?season='. $season . '&number=' . $episode;
		$response = $this->getFile($url);
		if (is_array($response)) {
			$ep = new Episode($response);
		}
		return $ep;
	}

	/**
	 * Returns episodes for a given show ID and ISO 8601 airdate
	 *
	 * @param $ID
	 * @param $airdate
	 *
	 * @return Episode|mixed
	 */
	function getEpisodesByAirdate($ID, $airdate)
	{
		$url = self::APIURL . '/shows/' . $ID . '/episodesbydate?date=' . date('Y-m-d', strtotime($airdate));
		$episodes = $this->getFile($url);

		$allEpisodes = array();
		if (is_array($episodes)) {
			foreach ($episodes as $episode) {
				$ep = new Episode($episode);
				array_push($allEpisodes, $ep);
			}
		}
		return $allEpisodes;
	}

	/**
	 * Takes in a show ID and outputs all of the cast members in the form (actor, character)
	 *
	 * @param $ID
	 *
	 * @return array
	 */
	function getCastByShowID($ID)
	{
		$url = self::APIURL . '/shows/' . $ID . '/cast';
		$people = $this->getFile($url);

		$cast = array();
		foreach($people as $person) {
			$actor = new Actor($person['person']);
			$character = new Character($person['character']);
			array_push($cast, array($actor, $character));
		}

		return $cast;
	}

	/**
	 * Gets a list of all shows in the database. Page number is optional (caps display at 250 results)
	 *
	 * @param null $page
	 *
	 * @return array
	 */
	function getAllShowsByPage($page=null)
	{
		if($page == null){
			$url = self::APIURL . '/shows';
		}else{
			$url = self::APIURL . '/shows?page' . $page;
		}

		$shows = $this->getFile($url);

		$relevant_shows = array();
		foreach($shows as $series){
			$TVShow = new TVShow($series);
			array_push($relevant_shows, $TVShow);
		}
		return $relevant_shows;
	}

	/**
	 * Gets an actor by their ID
	 *
	 * @param $ID
	 *
	 * @return Actor
	 */
	function getPersonByID($ID)
	{
		$url = self::APIURL . '/people/' . $ID;
		$show = $this->getFile($url);
		return new Actor($show);
	}

	/**
	 * Gets an array of all the shows a particular actor has been in
	 *
	 * @param $ID
	 *
	 * @return array
	 */
	function getCastCreditsByID($ID)
	{
		$url = self::APIURL . '/people/' . $ID . '/castcredits?embed=show';
		$castCredit = $this->getFile($url);

		$shows_appeared = array();
		foreach($castCredit as $series) {
			$TVShow = new TVShow($series['_embedded']['show']);
			array_push($shows_appeared, $TVShow);
		}
		return $shows_appeared;
	}

	/**
	 * Gets the position worked at the tv show in a tuple with the tvshow
	 *
	 * @param $ID
	 *
	 * @return array
	 */
	function getCrewCreditsByID($ID)
	{
		$url = self::APIURL . '/people/' . $ID . '/crewcredits?embed=show';
		$crewCredit = $this->getFile($url);

		$shows_appeared = array();
		foreach($crewCredit as $series) {
			$position = $series['type'];
			$TVShow = new TVShow($series['_embedded']['show']);
			array_push($shows_appeared, array($position, $TVShow));
		}
		return $shows_appeared;
	}

	/**
	 * Function used to get the data from the URL and return the results in an array
	 *
	 * @param $url
	 *
	 * @return mixed
	 */
	private function getFile($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($ch);
		curl_close($ch);

		$response = json_decode($result, TRUE);
		if (is_array($response) && count($response) > 0 && (!isset($response['status']) || $response['status'] != '404')) {
			return $response;
		} else {
			return false;
		}
	}
};

?>
