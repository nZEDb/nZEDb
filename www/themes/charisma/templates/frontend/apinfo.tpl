<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:newznab="{$serverroot}rss-info/">
	<channel>
		<atom:link href="{$serverroot}{$smarty.server.REQUEST_URI|escape:"htmlall"|substr:1}" rel="self"
				   type="application/rss+xml"/>
		<title>{$site->title|escape}</title>
		<description>{$site->title|escape} Nzb Feed</description>
		<link>{$serverroot}</link>
		<language>en-gb</language>
		<webMaster>{$site->email} ({$site->title|escape})</webMaster>
		<category>{$site->meta_keywords}</category>
		<image>
			<url>{$serverroot}themes/charisma/images/logo.png</url>
			<title>{$site->title|escape}</title>
			<link>{$serverroot}</link>
			<description>Visit {$site->title|escape} - {$site->strapline|escape}</description>
		</image>
		<item>
			<title>{$release.searchname|escape:"htmlall"}</title>
			<guid isPermaLink="true">{$serverroot}details/{$release.guid}</guid>
			<link>{$serverroot}nfo/{$release.guid}</link>
			<pubDate>{$release.postdate|phpdate_format:"DATE_RSS"}</pubDate>
			<description>{$nfoutf|escape:"htmlall"}</description>
			<enclosure url="{$serverroot}api?t=getnfo&amp;id={$release.guid}&amp;raw=1&amp;i={$uid}&amp;r={$rsstoken}"
					   length="{$nfoutf|count_characters:true}" type="text/x-nfo"/>
		</item>
	</channel>
</rss>