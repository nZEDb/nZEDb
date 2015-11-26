<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:newznab="http://www.newznab.com/DTD/2010/feeds/attributes/" encoding="utf-8">
	<channel>
		<atom:link href="{$serverroot}api" rel="self" type="application/rss+xml" />
		<title>{$site->title|escape}</title>
		<description>{$site->title|escape} Api Detail</description>
		<link>{$serverroot}</link>
		<language>en-gb</language>
		<webMaster>{$site->email} ({$site->title|escape})</webMaster>
		<category>{$site->meta_keywords}</category>
		<image>
			<url>{$serverroot}themes/shared/images/logo.png</url>
			<title>{$site->title|escape}</title>
			<link>{$serverroot}</link>
			<description>Visit {$site->title|escape} - {$site->strapline|escape}</description>
		</image>
		{foreach from=$releases item=release}
			<item>
				<title>{$release.searchname}</title>
				<guid isPermaLink="true">{$serverroot}details/{$release.guid}</guid>
				<link>{$serverroot}getnzb/{$release.guid}.nzb&amp;i={$uid}&amp;r={$rsstoken}{if $del=="1"}&amp;del=1{/if}</link>
				<comments>{$serverroot}details/{$release.guid}#comments</comments>
				<pubDate>{$release.adddate|phpdate_format:"DATE_RSS"}</pubDate>
				<category>{$release.category_name|escape:html}</category>
				<description>{$release.searchname}</description>
				<enclosure url="{$serverroot}getnzb/{$release.guid}.nzb&amp;i={$uid}&amp;r={$rsstoken}{if $del=="1"}&amp;del=1{/if}" length="{$release.size}" type="application/x-nzb" />
				{foreach from=$release.category_ids|parray:"," item=cat}
					<newznab:attr name="category" value="{$cat}" />
				{/foreach}
				<newznab:attr name="size" value="{$release.size}" />
				<newznab:attr name="files" value="{$release.totalpart}" />
				<newznab:attr name="poster" value="{$release.fromname|escape:html}" />
				{if $release.videos_id > 0 || $release.tv_episodes_id > 0}
					<newznab:attr name="videos_id" value="{$release.videos_id}" />
					{if $release.title != ""}
						<newznab:attr name="title" value="{$release.title|escape:html}" />
					{/if}
					{if $release.series > 0}
						<newznab:attr name="season" value="{$release.series}" />
					{/if}
					{if $release.episode > 0}
						<newznab:attr name="episode" value="{$release.episode}" />
					{/if}
					{if $release.firstaired != ""}
						<newznab:attr name="firstaired" value="{$release.firstaired|phpdate_format:"DATE_RSS"}" />
					{/if}
					{if $release.tvdb > 0}
						<newznab:attr name="tvdbid" value="{$release.tvdb}" />
					{/if}
					{if $release.trakt > 0}
						<newznab:attr name="traktid" value="{$release.trakt}" />
					{/if}
					{if $release.tvrage > 0}
						<newznab:attr name="tvrageid" value="{$release.tvrage}" />
						<newznab:attr name="rageid" value="{$release.tvrage}" />
					{/if}
					{if $release.tvmaze > 0}
						<newznab:attr name="tvmazeid" value="{$release.tvmaze}" />
					{/if}
					{if $release.imdb > 0}
						<newznab:attr name="imdbid" value="tt{$release.imdb}" />
					{/if}
					{if $release.tmdb > 0}
						<newznab:attr name="tmdbid" value="{$release.tmdb}" />
					{/if}
				{/if}
				{if $release.imdbid != ""}
					<newznab:attr name="imdb" value="{$release.imdbid}" />
				{/if}
				{if $release.tmdb > 0}
					<newznab:attr name="tmdbid" value="tt{$release.tmdb}" />
				{/if}
				{if $release.anidbid > 0}
					<newznab:attr name="anidbid" value="tt{$release.anidb}" />
				{/if}
				{if $release.nfostatus == 1}
					<newznab:attr name="info" value="{$serverroot}api?t=info&amp;id={$release.guid}&amp;r={$rsstoken}" />
				{/if}
				<newznab:attr name="grabs" value="{$release.grabs}" />
				<newznab:attr name="comments" value="{$release.comments}" />
				<newznab:attr name="password" value="{$release.passwordstatus}" />
				<newznab:attr name="usenetdate" value="{$release.postdate|phpdate_format:"DATE_RSS"}" />
				<newznab:attr name="group" value="{$release.group_name|escape:html}" />
				{if $release.coverurl != ""}
					<newznab:attr name="coverurl" value="{$release.coverurl}" />
				{/if}
			</item>
		{/foreach}
	</channel>
</rss>
