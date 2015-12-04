<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"
	 xmlns:newznab="http://www.newznab.com/DTD/2010/feeds/attributes/" encoding="utf-8">
	<channel>
		<atom:link href="{$serverroot}api" rel="self" type="application/rss+xml"/>
		<title>{$site->title|escape}</title>
		<description>{$site->title|escape} Api Detail</description>
		<link>{$serverroot}</link>
		<language>en-gb</language>
		<webMaster>{$site->email} ({$site->title|escape})</webMaster>
		<category>{$site->meta_keywords}</category>
		<image>
			<url>{$serverroot}themes_shared/images/logo.png</url>
			<title>{$site->title|escape}</title>
			<link>{$serverroot}</link>
			<description>Visit {$site->title|escape} - {$site->strapline|escape}</description>
		</image>
		{foreach from=$comments item=comm}
			<item>
				<title>{$comm.username}</title>
				<guid isPermaLink="true">{$serverroot}details/{$comm.guid}&amp;comment={$comm.id}</guid>
				<link>{$serverroot}details/{$comm.guid}&amp;comment={$comm.id}</link>
				<pubDate>{$comm.createddate|phpdate_format:"DATE_RSS"}</pubDate>
				<description>{$comm.text|escape:"htmlall"}</description>
			</item>
		{/foreach}
	</channel>
</rss>