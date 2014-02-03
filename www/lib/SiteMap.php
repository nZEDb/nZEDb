<?php

class SiteMap
{
	public $type = '';
	public $name = '';
	public $loc = '';
	public $priority = '';
	public $changefreq = '';

	function Sitemap($t, $n, $l, $p, $c)
	{
		$this->type = $t;
		$this->name = $n;
		$this->loc = $l;
		$this->priority = $p;
		$this->changefreq = $c;
	}
}
?>
