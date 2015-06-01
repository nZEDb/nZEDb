<?php

use nzedb\Contents;
use nzedb\SiteMap;

$te = $page->smarty;
$arPages = array();
$arPages[] = buildURL("Home", "Home Page", "/", 'daily', '1.0');

$role = 0;
if ($page->userdata != null) {
	$role = $page->userdata["role"];
}

// Useful links.
$contents = new Contents(['Settings' => $page->settings]);
$contentlist = $contents->getForMenuByTypeAndRole(Contents::TYPEUSEFUL, $role);
foreach ($contentlist as $content) {
	$arPages[] = buildURL("Useful Links", $content->title, '/content/' . $content->id . $content->url, 'monthly', '0.50');
}

// Articles.
$contentlist = $contents->getForMenuByTypeAndRole(Contents::TYPEARTICLE, $role);
foreach ($contentlist as $content) {
	$arPages[] = buildURL("Articles", $content->title, '/content/' . $content->id . $content->url, 'monthly', '0.50');
}

// Static pages.
$arPages[] = buildURL("Useful Links", "Contact Us", "/contact-us", 'yearly', '0.30');
$arPages[] = buildURL("Useful Links", "Contents", "/content", 'weekly', '0.50');
$arPages[] = buildURL("Useful Links", "Site Map", "/sitemap", 'weekly', '0.50');

if ($page->userdata != null) {
	$arPages[] = buildURL("Useful Links", "Rss Feeds", "/rss", 'weekly', '0.50');
	$arPages[] = buildURL("Useful Links", "API", "/apihelp", 'weekly', '0.50');

	$arPages[] = buildURL("Nzb", "Search Nzb", "/search", 'weekly', '0.50');
	$arPages[] = buildURL("Nzb", "New Releases", "/newposter", 'daily', '0.50');
	$arPages[] = buildURL("Nzb", "Browse Nzb", "/browse", 'daily', '0.80');
	$arPages[] = buildURL("Nzb", "Browse Groups", "/browsegroup", 'daily', '0.80');
	$arPages[] = buildURL("Nzb", "Movies", "/movies", 'daily', '0.80');
	$arPages[] = buildURL("Nzb", "TV Series", "/series", 'daily', '0.80');
	$arPages[] = buildURL("Nzb", "Anime", "/anime", 'daily', '0.80');
	$arPages[] = buildURL("Nzb", "Music", "/music", 'daily', '0.80');
	$arPages[] = buildURL("Nzb", "Console", "/console", 'daily', '0.80');

	$arPages[] = buildURL("Forum", "Forum", "/forum", 'daily', '0.80');

	$arPages[] = buildURL("User", "Cart", "/cart", 'weekly', '0.50');
	$arPages[] = buildURL("User", "Profile", "/profile", 'weekly', '0.50');
}

// Echo appropriate site map.
asort($arPages);
$page->smarty->assign('sitemaps', $arPages);

if (isset($_GET["type"]) && $_GET["type"] == "xml") {
	echo $page->smarty->fetch('sitemap-xml.tpl');
} else {
	$page->title = $page->settings->getSetting('title') . " site map";
	$page->meta_title = $page->settings->getSetting('title') . " site map";
	$page->meta_keywords = "sitemap,site,map";
	$page->meta_description = $page->settings->getSetting('title') . " site map shows all our pages.";
	$page->content = $page->smarty->fetch('sitemap.tpl');
	$page->render();
}

function buildURL($type, $name, $url, $freq = 'daily', $p = '1.0')
{
	$s = new SiteMap($type, $name, $url, $freq, $p);
	return $s;
}
