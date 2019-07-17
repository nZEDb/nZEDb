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

namespace nzedb\http;


use nzedb\Category;
use nzedb\utility\Misc;


/**
 * Class XMLReturn
 *
 * @package nzedb
 */
class XML_Response
{

	/**
	 * @var string The buffered cData before final write
	 */
	protected $cdata;

	/**
	 * The RSS namespace used for the output
	 *
	 * @var string
	 */
	protected $namespace;

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
	public function __construct($options = array())
	{
		$defaults = [
			'Parameters' => null,
			'Data'       => null,
			'Server'     => null,
			'Offset'     => null,
			'Type'       => null,
		];
		$options += $defaults;

		$this->parameters = $options['Parameters'];
		$this->releases = $options['Data'];
		$this->server = $options['Server'];
		$this->offset = $options['Offset'];
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
					$this->namespace = 'newznab';
					return $this->returnApiRss();
					break;
				case 'rss':
					$this->namespace = 'nZEDb';
					return $this->returnApiRss();
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
		$w =& $this->xml;
		$s = $this->server;

		$w->startDocument('1.0', 'UTF-8');
		$w->startElement('caps');
		$this->addNode(['name' => 'server', 'data' => $s['server']]);
		$this->addNode(['name' => 'limits', 'data' => $s['limits']]);
		$this->addNode(['name' => 'registration', 'data' => $s['registration']]);
		$this->addNodes(['name' => 'searching', 'data' => $s['searching']]);
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
	protected function returnApiRss()
	{
		$w =& $this->xml;
		$w->startDocument('1.0', 'UTF-8');
		$this->includeRssAtom(); // Open RSS
		$w->startElement('channel'); // Open channel
		$this->includeRssAtomLink();
		$this->includeMetaInfo();
		$this->includeImage();
		$this->includeTotalRows();
		$this->includeLimits();
		$this->includeReleases();
		$w->endElement(); // End channel
		$w->endElement(); // End RSS
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
	protected function addNode(array $element)
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
	protected function addNodes($element)
	{
		$this->xml->startElement($element['name']);
		foreach($element['data'] AS $elem => $value) {
			$subelement['name'] = $elem;
			$subelement['data'] = $value;
			$this->addNode($subelement);
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
				$this->xml->startElement('subcat');
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
	 *
	 */
	protected function includeRssAtom()
	{
		switch($this->namespace) {
			case 'newznab':
				$url = 'http://www.newznab.com/DTD/2010/feeds/attributes/';
				break;
			case 'nZEDb':
			default:
				$url = $this->server['server']['url'] . 'rss-info/';
		}

		$this->xml->startElement('rss');
		$this->xml->writeAttribute('version', '2.0');
		$this->xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
		$this->xml->writeAttribute("xmlns:{$this->namespace}", $url);
		$this->xml->writeAttribute('encoding', 'utf-8');
	}

	/**
	 *
	 */
	protected function includeRssAtomLink()
	{
		$this->xml->startElement('atom:link');
		$this->xml->startAttribute('href');
		$this->xml->text($this->server['server']['url'] . ($this->namespace === 'newznab' ? 'api' : 'rss'));
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

		switch($this->namespace) {
			case 'newznab':
				$path = 'apihelp/';
				$tag = 'API';
				break;
			case 'nZEDb':
			default:
				$path = 'rss-info/';
				$tag = 'RSS';
		}

		$this->xml->writeElement('title', $server['title']);
		$this->xml->writeElement('description', $server['title'] . " {$tag} Details");
		$this->xml->writeElement('link', $server['url']);
		$this->xml->writeElement('language', 'en-gb');
		$this->xml->writeElement('webMaster', $server['email'] . ' ' . $server['title']);
		$this->xml->writeElement('category', $server['meta']);
		$this->xml->writeElement('generator', 'nZEDb');
		$this->xml->writeElement('ttl', '10');
		$this->xml->writeElement('docs', $this->server['server']['url'] . $path);
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
	 * Adds total count of releases to the XML
	 */
	public function includeTotalRows()
	{
		$this->xml->startElement($this->namespace.":response");
		$this->xml->writeAttribute('offset', $this->offset);
		$this->xml->writeAttribute('total', isset($this->releases[0]['_totalrows']) ? $this->releases[0]['_totalrows'] : 0);
		$this->xml->endElement();
	}

    public function includeLimits()
    {
        $this->xml->startElement($this->namespace.':apilimits');
        $this->xml->writeAttribute('apiCurrent', $this->parameters['apirequests']);
        $this->xml->writeAttribute('apiMax', $this->parameters['apilimit']);
        $this->xml->writeAttribute('grabCurrent', $this->parameters['grabs']);
        $this->xml->writeAttribute('grabMax', $this->parameters['downloadlimit']);
        $this->xml->endElement();
    }

	/**
	 * Loop through the releases and add their info to the XML stream
	 */
	public function includeReleases()
	{
		if(is_array($this->releases) && !empty($this->releases)) {
			foreach ($this->releases AS $this->release) {
				$this->xml->startElement('item');
				$this->includeReleaseMain();
				$this->setZedAttributes();
				$this->xml->endElement();
			}
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
		if ($this->namespace === 'newznab') {
			$this->xml->writeElement('description', $this->release['searchname']);
		} else {
			$this->writeRssCdata();
		}
		if((isset($this->parameters['dl']) && $this->parameters['dl'] == 1) || !isset($this->parameters['dl'])) {
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
			if(($this->release['videos_id'] > 0 || $this->release['tv_episodes_id'] > 0) && $this->namespace === 'newznab') {
				$this->setTvAttr();
			}

			if (isset($this->release['imdbid']) && $this->release['imdbid'] > 0) {
				$this->writeZedAttr('imdb', $this->release['imdbid']);
			}
			if (isset($this->release['anidbid']) && $this->release['anidbid'] > 0) {
				$this->writeZedAttr('anidbid', $this->release['anidbid']);
			}
			if (isset($this->release['predb_id']) && $this->release['predb_id'] > 0) {
				$this->writeZedAttr('prematch', 1);
			}
			if (isset($this->release['nfostatus']) && $this->release['nfostatus'] == 1) {
				$this->writeZedAttr(
					'info',
					$this->server['server']['url'] .
					"api?t=info&id={$this->release['guid']}&r={$this->parameters['token']}"
				);
			}
			$this->writeZedAttr('grabs', $this->release['grabs']);
			$this->writeZedAttr('comments', $this->release['comments']);
			$this->writeZedAttr('password', $this->release['passwordstatus']);
			$this->writeZedAttr('usenetdate', date_format(date_create($this->release['postdate']), 'D, d M Y H:i:s O'));
			$this->writeZedAttr('group',
				(isset($this->release['group_name']) ? $this->release['group_name'] : ''));
		}
	}

	/**
	 * Writes the TV Specific attributes
	 */
	protected function setTvAttr()
	{
		if (!empty($this->release['title'])) {
			$this->writeZedAttr('title', $this->release['title']);
		}
		if (isset($this->release['series']) && $this->release['series'] > 0) {
			$this->writeZedAttr('season', $this->release['series']);
		}
		if (isset($this->release['episode']) && $this->release['episode'] > 0) {
			$this->writeZedAttr('episode', $this->release['episode']);
		}
		if (!empty($this->release['firstaired'])) {
			$this->writeZedAttr('tvairdate', $this->release['firstaired']);
		}
		if (isset($this->release['tvdb']) && $this->release['tvdb'] > 0) {
			$this->writeZedAttr('tvdbid', $this->release['tvdb']);
		}
		if (isset($this->release['trakt']) && $this->release['trakt'] > 0) {
			$this->writeZedAttr('traktid', $this->release['trakt']);
		}
		if (isset($this->release['tvrage']) && $this->release['tvrage'] > 0) {
			$this->writeZedAttr('tvrageid', $this->release['tvrage']);
			$this->writeZedAttr('rageid', $this->release['tvrage']);
		}
		if (isset($this->release['tvmaze']) && $this->release['tvmaze'] > 0) {
			$this->writeZedAttr('tvmazeid', $this->release['tvmaze']);
		}
		if (isset($this->release['imdb']) && $this->release['imdb'] > 0) {
			$this->writeZedAttr('imdbid', str_pad($this->release['imdb'], 7, '0', STR_PAD_LEFT));
		}
		if (isset($this->release['tmdb']) && $this->release['tmdb'] > 0) {
			$this->writeZedAttr('tmdbid', $this->release['tmdb']);
		}
	}

	/**
	 * Writes individual zed (newznab) type attributes
	 *
	 * @param string $name The namespaced attribute name tag
	 * @param string $value The namespaced attribute value
	 */
	protected function writeZedAttr($name, $value)
	{
		$this->xml->startElement($this->namespace . ":attr");
		$this->xml->writeAttribute('name', $name);
		$this->xml->writeAttribute('value', $value);
		$this->xml->endElement();
	}

	/**
	 * Writes the cData (HTML format) for the RSS feed
	 * Also calls supplementary cData writes depending upon post process
	 */
	protected function writeRssCdata()
	{
		$this->cdata = '';

		$w = $this->xml;
		$r = $this->release;
		$s = $this->server;
		$p = $this->parameters;

		$this->cdata = "\n\t<div>\n";
		switch(true) {
			case !empty($r['cover']):
				$dir = 'movies';
				$column = 'imdbid';
				break;
			case !empty($r['mu_cover']):
				$dir = 'music';
				$column = 'musicinfo_id';
				break;
			case !empty($r['co_cover']):
				$dir = 'console';
				$column = 'consoleinfo_id';
				break;
			case !empty($r['bo_cover']):
				$dir = 'books';
				$column = 'bookinfo_id';
				break;
		}
		if (isset($dir) && isset($column)) {
			$dcov = ($dir === 'movies' ? '-cover' : '');
			$this->cdata .=
				"\t<img style=\"margin-left:10px;margin-bottom:10px;float:right;\" " .
				"src=\"{$s['server']['url']}covers/{$dir}/{$r[$column]}{$dcov}.jpg\" " .
				"width=\"120\" alt=\"{$r['searchname']}\" />\n";
		}
		$size = Misc::bytesToSizeString($r['size']);
		$this->cdata .=
			"\t<li>ID: <a href=\"{$s['server']['url']}details/{$r['guid']}\">{$r['guid']}</a></li>\n" .
			"\t<li>Name: {$r['searchname']}</li>\n" .
			"\t<li>Size: {$size}</li>\n" .
			"\t<li>Category: <a href=\"{$s['server']['url']}browse?t={$r['categories_id']}\">{$r['category_name']}</a></li>\n" .
			"\t<li>Group: <a href=\"{$s['server']['url']}browse?g={$r['group_name']}\">{$r['group_name']}</a></li>\n" .
			"\t<li>Poster: {$r['fromname']}</li>\n" .
			"\t<li>Posted: {$r['postdate']}</li>\n";

		switch ($r['passwordstatus']) {
			case 0:
				$pstatus = 'None';
				break;
			case 1:
				$pstatus = 'Possibly Passworded';
				break;
			case 2:
				$pstatus = 'Probably not viable';
				break;
			case 10:
				$pstatus = 'Passworded';
				break;
			default:
				$pstatus = 'Unknown';
		}
		$this->cdata .= "\t<li>Password: {$pstatus}</li>\n";
		if ($r['nfostatus'] == 1) {
			$this->cdata .=
				"\t<li>Nfo: " .
				"<a href=\"{$s['server']['url']}api?t=nfo&id={$r['guid']}&raw=1&i={$p['uid']}&r={$p['token']}\">" .
				"{$r['searchname']}.nfo</a></li>\n";
		}

		if ($r['parentid'] == Category::MOVIE_ROOT && $r['imdbid'] != '') {
			$this->writeRssMovieInfo();
		} else if ($r['parentid'] == Category::MUSIC_ROOT && $r['musicinfo_id'] > 0) {
			$this->writeRssMusicInfo();
		} else if ($r['parentid'] == Category::GAME_ROOT && $r['consoleinfo_id'] > 0) {
			$this->writeRssConsoleInfo();
		}
		$w->startElement('description');
		$w->writeCdata($this->cdata . "\t</div>");
		$w->endElement();
	}

	/**
	 * Writes the Movie Info for the RSS feed cData
	 */
	protected function writeRssMovieInfo()
	{
		$r = $this->release;

		$movieCol = ['rating', 'plot', 'year', 'genre', 'director', 'actors'];

		$cData = $this->buildCdata($movieCol);

		$this->cdata .=
			"\t<li>Imdb Info:
				\t<ul>
					\t<li>IMDB Link: <a href=\"http://www.imdb.com/title/tt{$r['imdbid']}/\">{$r['searchname']}</a></li>\n
					\t{$cData}
				\t</ul>
			\t</li>
			\n";
	}

	/**
	 * Writes the Music Info for the RSS feed cData
	 */
	protected function writeRssMusicInfo()
	{
		$r = $this->release;
		$tData = $cDataUrl = '';

		$musicCol = ['mu_artist', 'mu_genre', 'mu_publisher', 'mu_releasedate', 'mu_review'];

		$cData = $this->buildCdata($musicCol);

		if ($r['mu_url'] !== '' ) {
			$cDataUrl = "<li>Amazon: <a href=\"{$r['mu_url']}\">{$r['mu_title']}</a></li>";
		}

		$this->cdata .=
			"\t<li>Music Info:
			<ul>
			{$cDataUrl}
			{$cData}
			</ul>
			</li>\n";
		if ($r['mu_tracks'] != '') {
			$tracks = explode('|', $r['mu_tracks']);
			if (count($tracks) > 0) {
				foreach ($tracks AS $track) {
					$track = trim($track);
					$tData .= "<li>{$track}</li>";
				}
			}
			$this->cdata .= "
			<li>Track Listing:
				<ol>
				{$tData}
				</ol>
			</li>\n";
		}
	}

	/**
	 * Writes the Console Info for the RSS feed cData
	 */
	protected function writeRssConsoleInfo()
	{
		$r = $this->release;
		$gamesCol = ['co_genre', 'co_publisher', 'year', 'co_review'];

		$cData = $this->buildCdata($gamesCol);

		$this->cdata .= "
		<li>Console Info:
			<ul>
				<li>Amazon: <a href=\"{$r['co_url']}\">{$r['co_title']}</a></li>\n
				{$cData}
			</ul>
		</li>\n";
	}

	/**
	 * Accepts an array of values to loop through to build cData from the release info
	 *
	 * @param array $columns The columns in the release we need to insert
	 *
	 * @return string The HTML format cData
	 */
	protected function buildCdata($columns)
	{
		$r = $this->release;

		$cData = '';

		foreach ($columns AS $info) {
			if (!empty($r[$info])) {
				if ($info == 'mu_releasedate') {
					$ucInfo = 'Released';
					$rDate = date('Y-m-d', strtotime($r[$info]));
					$cData .= "<li>{$ucInfo}: {$rDate}</li>\n";
				} else {
					$ucInfo = ucfirst(preg_replace('/^[a-z]{2}_/i', '', $info));
					$cData .= "<li>{$ucInfo}: {$r[$info]}</li>\n";
				}
			}
		}

		return $cData;
	}
}
