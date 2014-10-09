<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:newznab="http://www.newznab.com/DTD/2010/feeds/attributes/" encoding="utf-8">
	<channel>
		<atom:link href="{$serverroot}api" rel="self" type="application/rss+xml" />
		<title>{$site->title|escape}</title>
		<description>{$site->title|escape} API Results</description>
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
		<newznab:response offset="{$offset}" total="{if $releases|@count > 0}{$releases[0]._totalrows}{else}0{/if}" />
		{foreach from=$releases item=release}
			<item>
				<title>{$release.searchname|escape:html}</title>
				<guid isPermaLink="true">{$serverroot}details/{$release.guid}</guid>
				<link>{$serverroot}getnzb/{$release.guid}.nzb&amp;i={$uid}&amp;r={$rsstoken}</link>
				<comments>{$serverroot}details/{$release.guid}#comments</comments>
				<pubDate>{$release.adddate|phpdate_format:"DATE_RSS"}</pubDate>
				<category>{$release.category_name|escape:html}</category>
				<description>{$release.searchname|escape:html}</description>
				<enclosure url="{$serverroot}getnzb/{$release.guid}.nzb&amp;i={$uid}&amp;r={$rsstoken}" length="{$release.size}" type="application/x-nzb" />
				{foreach from=$release.category_ids|parray:"," item=cat}
					<newznab:attr name="category" value="{$cat}" />
				{/foreach}
				<newznab:attr name="size" value="{$release.size}" />
				{if $release.coverurl != ""}
					<newznab:attr name="coverurl" value="{$serverroot}covers/{$release.coverurl}" />
				{/if}
				{if $extended=="1"}
					<newznab:attr name="files" value="{$release.totalpart}" />
					<newznab:attr name="poster" value="{$release.fromname|escape:html}" />
					{if $release.season != ""}
						<newznab:attr name="season" value="{$release.season}" />
					{/if}
					{if $release.episode != ""}
						<newznab:attr name="episode" value="{$release.episode}" />
					{/if}
					{if $release.rageid != "-1" && $release.rageid != "-2"}
						<newznab:attr name="rageid" value="{$release.rageid}" />
					{/if}
					{if $release.tvtitle != ""}
						<newznab:attr name="tvtitle" value="{$release.tvtitle|escape:html}" />
					{/if}
					{if $release.tvairdate != ""}
						<newznab:attr name="tvairdate" value="{$release.tvairdate|phpdate_format:"DATE_RSS"}" />
					{/if}
					{if $release.imdbid != ""}
						<newznab:attr name="imdb" value="{$release.imdbid}" />
					{/if}
					<newznab:attr name="grabs" value="{$release.grabs}" />
					<newznab:attr name="comments" value="{$release.comments}" />
					<newznab:attr name="password" value="{$release.passwordstatus}" />
					<newznab:attr name="usenetdate" value="{$release.postdate|phpdate_format:"DATE_RSS"}" />
					<newznab:attr name="group" value="{$release.group_name|escape:html}" />
				{/if}
			</item>
		{/foreach}
	</channel>
</rss>
