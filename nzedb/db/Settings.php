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
 * @link <http://www.gnu.org/licenses/>.
 * @author niel
 * @copyright 2014 nZEDb
 */
namespace nzedb\db;

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'config.php';

use nzedb\utility\Utility;

class Settings extends \Sites
{
	public function __construct(array $options = array())
	{
		parent::__construct();
	}
}

/*
 * Putting procedural stuff inside class scripts like this is BAD. Do not use this as an excuse to do more.
 * This is a temporary measure until a proper frontend for cli stuff can be implemented with li3.
 */
if ($argc > 1) {

	if (Utility::isCLI()) {
		$settings = new Settings();
		echo $settings->getSetting($argv[1]);
	}
}

?>
