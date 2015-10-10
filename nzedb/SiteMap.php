<?php
namespace nzedb;

class SiteMap
{
	public $type = '';
	public $name = '';
	public $loc = '';
	public $priority = '';
	public $changefreq = '';

	public function __construct($t, $n, $l, $p, $c)
	{
		$this->type = $t;
		$this->name = $n;
		$this->loc = $l;
		$this->priority = $p;
		$this->changefreq = $c;
	}
}
