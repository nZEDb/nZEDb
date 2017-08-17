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
	}

	public function fetch()
	{
		if (empty($this->msgid)) {
			$this->error("{:red}No message-id (msgid=) supplied.{:end}");
			$this->msgid = $this->in("message-id?");
		}

		$this->out("{:cyan}Message-ID used: '{$this->msgid}'{:end}");

		$nntp = new NNTP(['Settings' => new DB()]);
	}

	public function run()
	{
		if (!$this->request->args()) {
			return $this->_help();
		}

		return false;
	}

}
