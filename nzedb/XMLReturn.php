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
 * @author    ruhllatio
 * @copyright 2016 nZEDb
 */

namespace nzedb;

/**
 * Class XMLReturn
 *
 * @package nzedb
 */
class XMLReturn
{
	/**
	 * @var mixed
	 */
	protected $releases;

	/**
	 * @var mixed
	 */
	protected $server;

	/**
	 * @var mixed
	 */
	protected $type;

	/**
	 * @var \XMLWriter
	 */
	protected $xml;

	/**
	 * XMLReturn constructor.
	 *
	 * @param array $options
	 */
	public function __construct($options = array()) {
		$defaults = [
			'Releases'   => false,
			'Server'     => false,
			'Type'       => false,
		];
		$options += $defaults;

		$this->releases = $options['Releases'];
		$this->server = $options['Server'];
		$this->type = $options['Type'];

		$this->xml = new \XMLWriter();
		$this->xml->openMemory();
		$this->xml->setIndent(true);
	}

	/**
	 * @return bool|void
	 */
	public function returnXML()
	{
		if ($this->xml) {
			switch ($this->type) {
				case 'caps':
					return $this->returnCaps();
					break;
				case 'api':
					return $this->returnAPI();
					break;
				case 'rss':
					return $this->returnRSS();
					break;
			}
		}
		return false;
	}

	/**
	 * XML writes and returns the API capabilities
	 *
	 * @return string The XML Formatted string data
	 */
	protected function returnCaps()
	{
		$w = $this->xml;
		$s = $this->server;

		$w->startDocument('1.0', 'UTF-8');
		$w->startElement('caps');
		$w->startElement('server');
		foreach($s['server'] AS $attr => $val) {
			$w->startAttribute($attr);
			$w->text($val);
			$w->endAttribute();
		}
		$w->endElement();
		$w->startElement('limits');
		foreach($s['limits'] AS $attr => $val) {
			$w->startAttribute($attr);
			$w->text($val);
			$w->endAttribute();
		}
		$w->endElement();
		$w->startElement('registration');
		foreach($s['registration'] AS $attr => $val) {
			$w->startAttribute($attr);
			$w->text($val);
			$w->endAttribute();
		}
		$w->endElement();
		$w->startElement('searching');
		foreach($s['searching'] AS $searches => $search) {
			$w->startElement($searches);
			foreach($search AS $attr => $val) {
				$w->startAttribute($attr);
				$w->text($val);
				$w->endAttribute();
			}
			$w->endElement();
		}
		$w->endElement();
		$w->startElement('categories');
		foreach ($s['categories'] AS $p) {
			$w->startElement('category');
			$w->startAttribute('id');
			$w->text($p['id']);
			$w->endAttribute();
			$w->startAttribute('name');
			$w->text(html_entity_decode($p['title']));
			$w->endAttribute();
			if ($p['description'] != '') {
				$w->startAttribute('description');
				$w->text(html_entity_decode($p['description']));
				$w->endAttribute();
			}
			foreach($p['subcatlist'] AS $c) {
				$w->startElement('subcatlist');
				$w->startAttribute('id');
				$w->text($c['id']);
				$w->endAttribute();
				$w->startAttribute('name');
				$w->text(html_entity_decode($c['title']));
				$w->endAttribute();
				if ($c['description'] != '') {
					$w->startAttribute('description');
					$w->text(html_entity_decode($c['description']));
					$w->endAttribute();
				}
				$w->endElement();
			}
			$w->endElement();
		}
		$w->endElement();
		$w->endDocument();

		return $w->outputMemory();
	}

	/**
	 * XML writes and returns the API data
	 *
	 * @return string The XML Formatted string data
	 */
	protected function returnAPI()
	{
		$w = $this->xml;
		$this->xml->startDocument('1.0', 'UTF-8');
		$w->endDocument();

		return $w->outputMemory();
	}

	/**
	 * XML writes and returns the RSS data
	 *
	 * @return string The XML Formatted string data
	 */
	protected function returnRSS()
	{
		$w = $this->xml;
		$this->xml->startDocument('1.0', 'UTF-8');
		$w->endDocument();

		return $w->outputMemory();
	}
}