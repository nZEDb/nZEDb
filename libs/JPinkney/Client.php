<?php

/**
 * User: jpinkney
 * Date: 9/15/15
 * Time: 2:15 PM
 */

namespace libs\JPinkney;

/**
 * This is the file that you are going to include in each of your new projects
 */

use libs\JPinkney\TVMaze\TVMaze;

/* - Enable these when desired and pass options through __construct
use libs\JPinkney\TVMaze\TVProduction;
use libs\JPinkney\TVMaze\TVShow;
use libs\JPinkney\TVMaze\Actor;
use libs\JPinkney\TVMaze\Character;
use libs\JPinkney\TVMaze\Crew;
use libs\JPinkney\TVMaze\Episode;
*/

/**
 * Class Client
 *
 * @package JPinkney
 */
class Client
{
	/**
	 * @var TVMaze
	 */
	public $TVMaze;

	/**
	 * @param array $options
	 */
	public function __construct($options = array())
	{
		$defaults = [];
		$options += $defaults;

		$this->TVMaze = new TVMaze();
	}
}

?>
