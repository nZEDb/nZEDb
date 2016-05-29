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
	 * The trailing URL parameters on the request
	 *
	 * @var mixed
	 */
	protected $parameters;

	/**
	 * The release we are adding to the stream
	 *
	 * @var array
	 */
	protected $release;

	/**
	 * The retrieved releases we are returning from the API call
	 *
	 * @var mixed
	 */
	protected $releases;

	/**
	 * The various server variables and active categories
	 *
	 * @var mixed
	 */
	protected $server;

	/**
	 * The XML formatting operation we are returning
	 *
	 * @var mixed
	 */
	protected $type;

	/**
	 * The XMLWriter Class
	 *
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
			'Parameters' => false,
			'Releases'   => false,
			'Server'     => false,
			'Type'       => false,
		];
		$options += $defaults;

		$this->parameters = $options['Parameters'];
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
				case 'reg':
					return $this->returnReg();
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
		$this->loopSingleElementAttrs(['name' => 'server', 'data' => $s['server']]);
		$this->loopSingleElementAttrs(['name' => 'limits', 'data' => $s['limits']]);
		$this->loopSingleElementAttrs(['name' => 'registration', 'data' => $s['registration']]);
		$this->loopMultiElementAttributes(['name' => 'searching', 'data' => $s['searching']]);
		$this->writeCategoryListing();
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
		$this->includeRssAtom(); // Open RSS
		$w->startElement('channel'); // Open channel
		$this->includeRssAtomLink();
		$this->includeMetaInfo();
		$this->includeImage();
		$this->includeReleases();
		$w->endElement(); // End channel
		$w->endElement(); // End RSS
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

	/**
	 * @return string The XML formatted registration information
	 */
	protected function returnReg()
	{
		$this->xml->startDocument('1.0', 'UTF-8');
		$this->xml->startElement('register');
		$this->xml->writeAttribute('username', $this->parameters['username']);
		$this->xml->writeAttribute('password', $this->parameters['password']);
		$this->xml->writeAttribute('apikey', $this->parameters['token']);
		$this->xml->endElement();
		$this->xml->endDocument();

		return $this->xml->outputMemory();
	}

	/**
	 * Starts a new element, loops through the attribute data and ends the element
	 * @param array $element An array with the name of the element and the attribute data
	 */
	protected function loopSingleElementAttrs($element)
	{
		$this->xml->startElement($element['name']);
		foreach($element['data'] AS $attr => $val) {
			$this->xml->writeAttribute($attr, $val);
		}
		$this->xml->endElement();
	}

	/**
	 * Starts a new element, loops through the attribute data and ends the element
	 * @param array $element An array with the name of the element and the attribute data
	 */
	protected function loopMultiElementAttributes($element)
	{
		$this->xml->startElement($element['name']);
		foreach($element['data'] AS $name => $elem) {
			$this->xml->startElement($name);
			foreach ($elem AS $attr => $val) {
				$this->xml->writeAttribute($attr, $val);
			}
			$this->xml->endElement();
		}
		$this->xml->endElement();
	}

	/**
	 * Adds the site category listing to the XML feed
	 */
	protected function writeCategoryListing()
	{
		$this->xml->startElement('categories');
		foreach ($this->server['categories'] AS $p) {
			$this->xml->startElement('category');
			$this->xml->writeAttribute('id', $p['id']);
			$this->xml->writeAttribute('name', html_entity_decode($p['title']));
			if ($p['description'] != '') {
				$this->xml->writeAttribute('description', html_entity_decode($p['description']));
			}
			foreach($p['subcatlist'] AS $c) {
				$this->xml->startElement('subcatlist');
				$this->xml->writeAttribute('id', $c['id']);
				$this->xml->writeAttribute('name', html_entity_decode($c['title']));
				if ($c['description'] != '') {
					$this->xml->writeAttribute('description', html_entity_decode($c['description']));
				}
				$this->xml->endElement();
			}
			$this->xml->endElement();
		}
	}

	/**
	 * Adds RSS Atom information to the XML
	 */
	protected function includeRssAtom()
	{
		$this->xml->startElement('rss');
		$this->xml->writeAttribute('version', '2.0');
		$this->xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
		$this->xml->writeAttribute('xmlns:newznab', 'http://www.newznab.com/DTD/2010/feeds/attributes/');
		$this->xml->writeAttribute('encoding', 'utf-8');
	}

	/**
	 *
	 */
	protected function includeRssAtomLink()
	{
		$this->xml->startElement('atom:link');
		$this->xml->startAttribute('href');
		$this->xml->text($this->server['server']['url'] . 'api');
		$this->xml->endAttribute();
		$this->xml->startAttribute('rel');
		$this->xml->text('self');
		$this->xml->endAttribute();
		$this->xml->startAttribute('type');
		$this->xml->text('application/rss+xml');
		$this->xml->endAttribute();
		$this->xml->endElement();
	}

	/**
	 * Writes the channel information for the feed
	 */
	protected function includeMetaInfo()
	{
		$server = $this->server['server'];

		$this->xml->writeElement('title', $server['title']);
		$this->xml->writeElement('description', $server['title'] . ' API Details');
		$this->xml->writeElement('link', $server['url']);
		$this->xml->writeElement('language', 'en-gb');
		$this->xml->writeElement('webMaster', $server['email'] . ' ' . $server['title']);
		$this->xml->writeElement('category', $server['meta']);
		$this->xml->writeElement('generator', 'nZEDb');
		$this->xml->writeElement('ttl', '10');
		$this->xml->writeElement('docs', $this->server['server']['url'] . 'apihelp');
	}

	/**
	 * Adds nZEDB logo data to the XML
	 */
	protected function includeImage()
	{
		$this->xml->startElement('image');
		$this->xml->writeAttribute('url', $this->server['server']['url'] . 'themes/shared/img/logo.png');
		$this->xml->writeAttribute('title', $this->server['server']['title']);
		$this->xml->writeAttribute('link', $this->server['server']['url']);
		$this->xml->writeAttribute(
			'description',
			'Visit ' . $this->server['server']['title'] . ' - ' . $this->server['server']['strapline']
		);
		$this->xml->endElement();
	}

	/**
	 * Loop through the releases and add their info to the XML stream
	 */
	public function includeReleases()
	{
		foreach ($this->releases AS $this->release) {
			$this->xml->startElement('item');
			$this->includeReleaseMain();
			$this->setZedAttributes();
			$this->xml->endElement();
		}
	}

	/**
	 * Writes the primary release information
	 */
	public function includeReleaseMain()
	{
		$this->xml->writeElement('title', $this->release['searchname']);
		$this->xml->startElement('guid');
		$this->xml->writeAttribute('isPermaLink', 'true');
		$this->xml->text("{$this->server['server']['url']}details/{$this->release['guid']}");
		$this->xml->endElement();
		$this->xml->writeElement(
			'link',
			"{$this->server['server']['url']}getnzb/{$this->release['guid']}.nzb" .
			"&i={$this->parameters['uid']}" . "&r={$this->parameters['token']}" .
			($this->parameters['del'] == '1' ? "&del=1" : '')
		);
		$this->xml->writeElement('comments', "{$this->server['server']['url']}details/{$this->release['guid']}#comments");
		$this->xml->writeElement('pubDate', date(DATE_RSS, strtotime($this->release['adddate'])));
		$this->xml->writeElement('category', $this->release['category_name']);
		$this->xml->writeElement('description', $this->release['searchname']);
		$this->xml->startElement('enclosure');
		$this->xml->writeAttribute(
			'url',
			"{$this->server['server']['url']}getnzb/{$this->release['guid']}.nzb" .
			"&i={$this->parameters['uid']}" . "&r={$this->parameters['token']}" .
			($this->parameters['del'] == '1' ? "&del=1" : '')
		);
		$this->xml->writeAttribute('length', $this->release['size']);
		$this->xml->writeAttribute('type', 'application/x-nzb');
		$this->xml->endElement();
	}

	/**
	 * Writes the Zed (newznab) specific attributes
	 */
	protected function setZedAttributes()
	{
		$this->writeZedAttr('category', $this->release['categories_id']);
		$this->writeZedAttr('size', $this->release['size']);
		if (isset($this->release['coverurl']) && !empty($this->release['coverurl'])) {
			$this->writeZedAttr(
				'coverurl',
				$this->server['server']['url'] . "covers/{$this->release['coverurl']}"
			);
		}

		if ($this->parameters['extended'] == 1) {
			$this->writeZedAttr('files', $this->release['totalpart']);
			$this->writeZedAttr('poster', $this->release['fromname']);
			if($this->release['videos_id'] > 0 || $this->release['tv_episodes_id'] > 0) {
				$this->setTvAttributes();
			}

			switch (true) {
				case $this->release['imdbid'] > 0:
					$this->writeZedAttr('imdb', $this->release['imdbid']);
				case $this->release['anidbid'] > 0:
					$this->writeZedAttr('anidbid', $this->release['anidbid']);
				case $this->release['predb_id'] > 0:
					$this->writeZedAttr('prematch', 1);
				case $this->release['nfostatus'] == 1:
					$this->writeZedAttr(
						'info',
						$this->server['server']['url'] .
							"api?t=info&id={$this->release['guid']}&r={$this->parameters['token']}"
					);
			}
			$this->writeZedAttr('grabs', $this->release['grabs']);
			$this->writeZedAttr('comments', $this->release['comments']);
			$this->writeZedAttr('password', $this->release['passwordstatus']);
			$this->writeZedAttr('usenetdate', date(DATE_RSS, strtotime($this->release['postdate'])));
			$this->writeZedAttr('group', $this->release['group_name']);
		}
	}

	/**
	 * Writes the TV Specific attributes
	 */
	protected function setTvAttributes()
	{
		switch(true) {
			case !empty($this->release['title']):
				$this->writeZedAttr('title', $this->release['title']);
			case $this->release['series'] > 0:
				$this->writeZedAttr('season', $this->release['series']);
			case $this->release['episode'] > 0:
				$this->writeZedAttr('episode', $this->release['episode']);
			case !empty($this->release['firstaired']):
				$this->writeZedAttr('firstaired', date(DATE_RSS, strtotime($this->release['firstaired'])));
			case $this->release['tvdb'] > 0:
				$this->writeZedAttr('tvdbid', $this->release['tvdb']);
			case $this->release['trakt'] > 0:
				$this->writeZedAttr('traktid', $this->release['trakt']);
			case $this->release['tvrage'] > 0:
				$this->writeZedAttr('tvrageid', $this->release['tvrage']);
				$this->writeZedAttr('rageid', $this->release['tvrage']);
			case $this->release['tvmaze'] > 0:
				$this->writeZedAttr('tvmazeid', $this->release['tvmaze']);
			case $this->release['imdb'] > 0:
				$this->writeZedAttr('imdbid', str_pad($this->release['imdb'], 7, '0', STR_PAD_LEFT));
			case $this->release['tmdb'] > 0:
				$this->writeZedAttr('tmdbid', $this->release['tmdb']);
		}
	}

	/**
	 * Writes individual zed (newznab) type attributes
	 *
	 * @param string $name The newznab attribute name tag
	 * @param string $value The newznab attribute value
	 */
	protected function writeZedAttr($name, $value) {
		$this->xml->startElement('newznab:attr');
		$this->xml->writeAttribute('name', $name);
		$this->xml->writeAttribute('value', $value);
		$this->xml->endElement();
	}
}