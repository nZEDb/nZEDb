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
 * @copyright 2017 nZEDb
 */

namespace app\extensions\command;

use app\extensions\util\Yenc;
use app\models\Settings;
use nzedb\db\DB;
use nzedb\NNTP;


/**
 * Lets you manually fetch data from your U.S.P.
 *
 *
 *
 * @package app\extensions\command
 */
class Usenet extends \app\extensions\console\Command
{
	public $group = 'alt.binaries.moovee';

	public $msgid = '<0c-A40009D167$y23340x3Gi4781d@I40$O3b586991.Y1al3kC5>';

	public $showBody = false;

	public $showHeader = false;

	/**
	 * @var \nzedb\NNTP object
	 */
	private $usp = null;

	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		$defaults = [
			'classes'  => $this->_classes,
			'request'  => null,
			'response' => [],
		];
		parent::__construct($config + $defaults);

		$this->usp = new NNTP(['Settings' => new DB()]);

		$result = $this->usp->doConnect(Settings::value('..compressedheaders'));
		if ($result === true) {
			$this->out("{:green}Connected to USP{:end}");
		} else {
			$this->out("{:green}Damnit an error!!{:end}");
			throw new \ErrorException($result->getMessage());
		}
	}

	public function fetch()
	{
		if (empty($this->msgid)) {
			$this->error("{:red}No message-id (--msgid=) supplied.{:end}");
			$this->msgid = $this->in("message-id (complex ids [most] will need single-quoting)?");
		}

		if (empty($this->group)) {
			$this->error("{:red}No group name (--group=) supplied.{:end}");
			$this->group = $this->in("group name?");
		}

		$this->out("{:cyan}Using\nmessage-id: '{$this->msgid}'\nGroup: {$this->group}{:end}");

		$path = nZEDb_TMP . trim($this->msgid, '<>');
		if (!file_exists($path)) {
			mkdir($path);
		}

		$result = $this->getHeader($this->msgid, true);
		if ($this->usp->isError($result) === false) {
			file_put_contents($path . DS . 'headers.txt', $result . "\r\n");
			if ($this->showHeader) {
				$this->out("{:green}Headers{:end}");
				$this->out( $result);
			} else {
				$this->out("{:green}Fetched headers{:end}");
			}
		} else {
			$this->out("{:green}Damnit an error!!{:end}");
			throw new \ErrorException($result->getMessage());
		}

		$result = $this->getBody($this->msgid, true);
		if ($this->usp->isError($result) === false) {
			file_put_contents($path . DS . 'body.txt', $result . "\r\n");
			if ($this->showBody) {
				$this->out("{:green}Body{:end}");
				$this->out($result);
			} else {
				$this->out("{:green}Fetched body{:end}");
			}
			file_put_contents($path . DS . 'body.decoded', $this->decodeBody($result));
		} else {
			$this->out("{:green}Damnit an error!!{:end}");
			throw new \ErrorException($result->getMessage());
		}

		/*
		} else {
			$this->out("{:green}Damnit an error!!{:end}");
			throw new \ErrorException($article->getMessage());
		}
		*/
	}

	public function run()
	{
		if ($this->request->args() === null) {
			return $this->_help();
		}

		return false;
	}

	protected function getBody($identifier = null, $implode = false)
	{
		// Download the header.
		$header = $this->usp->getBody($identifier, $implode);

		return $header;
	}

	protected function getHeader($identifier = null, $implode = false)
	{
		// Download the header.
		$header = $this->usp->getHeader($identifier, $implode);

		return $header;
	}

	private function decodeBody(&$text)
	{
		return Yenc::decode($text);
		//return Yenc::decodeIgnore($text, ['name' => 'php']);
		//return false;
	}
}
