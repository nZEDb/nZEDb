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
		{foreach from=$releases item=release}
			<item>
				<title>{$release.searchname}</title>
				<guid isPermaLink="true">{$serverroot}details/{$release.guid}</guid>
				<link>{$serverroot}getnzb/{$release.guid}.nzb&amp;i={$uid}
				&amp;r={$rsstoken}{if $del=="1"}&amp;del=1{/if}</link>
				<comments>{$serverroot}details/{$release.guid}#comments</comments>
				<pubDate>{$release.adddate|phpdate_format:"DATE_RSS"}</pubDate>
				<category>{$release.category_name|escape:html}</category>
				<description>{$release.searchname}</description>
				<enclosure
						url="{$serverroot}getnzb/{$release.guid}.nzb&amp;i={$uid}&amp;r={$rsstoken}{if $del=="1"}&amp;del=1{/if}"
						length="{$release.size}" type="application/x-nzb"/>
				{foreach from=$release.category_ids|parray:"," item=cat}
					<newznab:attr name="category" value="{$cat}"/>
				{/foreach}
				<newznab:attr name="size" value="{$release.size}"/>
				<newznab:attr name="files" value="{$release.totalpart}"/>
				<newznab:attr name="poster" value="{$release.fromname|escape:html}"/>
				<newznab:attr name="guid" value="{$release.guid}"/>
				{if $release.season != ""}
					<newznab:attr name="season" value="{$release.season}"/>
				{/if}
				{if $release.episode != ""}
					<newznab:attr name="episode" value="{$release.episode}"/>
				{/if}
				{if $release.rageid != "-1" && $release.rageid != "-2"}
					<newznab:attr name="rageid" value="{$release.rageid}"/>
					{if $release.tvtitle != ""}
						<newznab:attr name="tvtitle" value="{$release.tvtitle|escape:html}"/>
					{/if}
					{if $release.tvairdate != ""}
						<newznab:attr name="tvairdate" value="{$release.tvairdate|phpdate_format:"DATE_RSS"}"/>
					{/if}
				{/if}
				{if $release.imdbid != ""}
					<newznab:attr name="imdb" value="{$release.imdbid}"/>
				{/if}
				{if $mov.title != ""}
					<newznab:attr name="imdbtitle" value="{$mov.title|escape:html}"/>
				{/if}
				{if $mov.tagline != ""}
					<newznab:attr name="imdbtagline" value="{$mov.tagline|escape:html}"/>
				{/if}
				{if $mov.plot != ""}
					<newznab:attr name="imdbplot" value="{$mov.plot|escape:html}"/>
				{/if}
				{if $mov.rating != ""}
					<newznab:attr name="imdbscore" value="{$mov.rating}"/>
				{/if}
				{if $mov.genre != ""}
					<newznab:attr name="genre" value="{$mov.genre|escape:html}"/>
				{/if}
				{if $mov.year != ""}
					<newznab:attr name="imdbyear" value="{$mov.year}"/>
				{/if}
				{if $mov.director != ""}
					<newznab:attr name="imdbdirector" value="{$mov.director|escape:html}"/>
				{/if}
				{if $mov.actors != ""}
					<newznab:attr name="imdbactors" value="{$mov.actors|escape:html}"/>
				{/if}
				{if $mov.cover == 1}
					<newznab:attr name="coverurl" value="{$serverroot}covers/movies/{$release.imdbid}-cover.jpg"/>
				{/if}
				{if $mov.backdrop == 1}
					<newznab:attr name="backdropurl" value="{$serverroot}covers/movies/{$release.imdbid}-backdrop.jpg"/>
				{/if}
				{if $release.musicinfoid != "" && $release.mi_title != ""}
					<newznab:attr name="album" value="{$release.mi_title|escape:html}"/>
				{/if}
				{if $release.musicinfoid != "" && $release.mi_artist != ""}
					<newznab:attr name="artist" value="{$release.mi_artist|escape:html}"/>
				{/if}
				{if $release.musicinfoid != "" && $release.mi_publisher != ""}
					<newznab:attr name="label" value="{$release.mi_publisher|escape:html}"/>
				{/if}
				{if $release.musicinfoid != "" && $release.mi_tracks != ""}
					<newznab:attr name="tracks" value="{$release.mi_tracks|escape:html}"/>
				{/if}
				{if $release.musicinfoid != "" && $release.mi_review != ""}
					<newznab:attr name="review" value="{$release.mi_review|escape:html}"/>
				{/if}
				{if $release.musicinfoid != "" && $release.mi_cover == "1"}
					<newznab:attr name="coverurl" value="{$serverroot}covers/music/{$release.musicinfoid}.jpg"/>
				{/if}
				{if $release.musicinfoid != "" && $release.music_genrename != ""}
					<newznab:attr name="genre" value="{$release.music_genrename|escape:html}"/>
				{/if}
				{if $release.bookinfoid != "" && $release.bi_author != ""}
					<newznab:attr name="author" value="{$release.bi_author|escape:html}"/>
				{/if}
				{if $release.bookinfoid != "" && $release.bi_title != ""}
					<newznab:attr name="booktitle" value="{$release.bi_title|escape:html}"/>
				{/if}
				{if $release.bookinfoid != "" && $release.bi_cover == "1"}
					<newznab:attr name="coverurl" value="{$serverroot}covers/book/{$release.bookinfoid}.jpg"/>
				{/if}
				{if $release.bookinfoid != "" && $release.bi_review != ""}
					<newznab:attr name="review" value="{$release.bi_review|escape:html}"/>
				{/if}
				{if $release.bookinfoid != "" && $release.bi_publishdate != ""}
					<newznab:attr name="publishdate" value="{$release.bi_publishdate|phpdate_format:"DATE_RSS"}"/>
				{/if}
				{if $release.bookinfoid != "" && $release.bi_publisher != ""}
					<newznab:attr name="publisher" value="{$release.bi_publisher|escape:html}"/>
				{/if}
				{if $release.bookinfoid != "" && $release.bi_pages != ""}
					<newznab:attr name="pages" value="{$release.bi_pages|escape:html}"/>
				{/if}
				{if $release.bookinfoid != "" && $release.bi_isbn != ""}
					<newznab:attr name="isbn" value="{$release.bi_isbn|escape:html}"/>
				{/if}
				<newznab:attr name="grabs" value="{$release.grabs}"/>
				<newznab:attr name="comments" value="{$release.comments}"/>
				<newznab:attr name="password" value="{$release.passwordstatus}"/>
				<newznab:attr name="usenetdate" value="{$release.postdate|phpdate_format:"DATE_RSS"}"/>
				<newznab:attr name="group" value="{$release.group_name|escape:html}"/>
			</item>
		{/foreach}
	</channel>
</rss>