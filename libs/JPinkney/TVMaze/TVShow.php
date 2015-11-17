<?php
/**
 * Created by PhpStorm.
 * User: joshpinkney
 * Date: 9/15/15
 * Time: 2:16 PM
 */

namespace libs\JPinkney\TVMaze;

//Check back here if we can move the episode data to the episode class later

/**
 * Class TVShow
 *
 * @package libs\JPinkney\TVMaze
 */
class TVShow extends TVProduction{

	/**
	 * @var
	 */
	public $type;
	/**
	 * @var
	 */
	public $language;
	/**
	 * @var
	 */
	public $genres;
	/**
	 * @var
	 */
	public $status;
	/**
	 * @var
	 */
	public $runtime;
	/**
	 * @var
	 */
	public $premiered;
	/**
	 * @var
	 */
	public $rating;
	/**
	 * @var
	 */
	public $weight;
	/**
	 * @var
	 */
	public $network_array;
	/**
	 * @var
	 */
	public $network;
	/**
	 * @var
	 */
	public $webChannel;
	/**
	 * @var
	 */
	public $externalIDs;
	/**
	 * @var string
	 */
	public $summary;
	/**
	 * @var
	 */
	public $nextAirDate;
	/**
	 * @var bool|string
	 */
	public $airTime;
	/**
	 * @var bool|string
	 */
	public $airDay;

	/**
	 * @var string
	 */
	public $country;

	/**
	 * @param $show_data
	 */
	function __construct($show_data){
		parent::__construct($show_data);
		$this->type = $show_data['type'];
		$this->language = $show_data['language'];
		$this->genres = $show_data['genres'];
		$this->status = $show_data['status'];
		$this->runtime = $show_data['runtime'];
		$this->premiered = $show_data['premiered'];
		$this->rating = $show_data['rating'];
		$this->weight = $show_data['weight'];
		$this->network_array = $show_data['network'];
		$this->network = $show_data['network']['name'];
		$this->country = $show_data['network']['country']['code'];
		if (count($show_data['webChannel']) > 0) {
			$this->country = $show_data['webChannel']['country']['code'];
		}
		$this->webChannel = $show_data['webChannel'];
		$this->externalIDs = $show_data['externals'];
		$this->summary = strip_tags($show_data['summary']);
		$this->akas = (isset($show_data['_embedded']['akas']) ? $show_data['_embedded']['akas'] : null);
	}

	/*
	 *
	 * This function is used to check whether or not the object contains any data
	 *
	 */
	/**
	 * @return bool
	 */
	function isEmpty()
	{
		return($this->id == null || $this->id == 0 && $this->url == null && $this->name == null);
	}

};

?>
