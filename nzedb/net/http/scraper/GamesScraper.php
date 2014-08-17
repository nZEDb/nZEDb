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
namespace nzedb\net\http\scraper;

require_once nZEDb_LIBS . 'simple_html_dom.php';

class GamesScraper extends \nzedb\net\http\Scraper
{
	/**
	 * Path to save any fetched images (covers, posters, etc.)
	 *
	 * @var string
	 */
	public $imgSavePath;


	/**
	 * @var \libs\simple_html_dom
	 */
	protected $_html;

	/**
	 * @var
	 */
	protected $_gameID;

	public function __construct()
	{
		$this->_html     = new simple_html_dom();
		$this->_editHtml = new simple_html_dom();
		if (isset($this->cookie)) {
			@$this->_getURL(self::STEAMURL);
		}
	}
}
