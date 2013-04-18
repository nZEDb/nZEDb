<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:newznab="http://www.newznab.com/DTD/2010/feeds/attributes/" encoding="utf-8">
<channel>
<atom:link href="{$serverroot}{$smarty.server.REQUEST_URI|escape:"htmlall"|substr:1}" rel="self" type="application/rss+xml" />
<title>{$site->title|escape}</title>
<description>{$site->title|escape} Nzb Feed</description>
<link>{$serverroot}</link>
<language>en-gb</language>
<webMaster>{$site->email} ({$site->title|escape})</webMaster>
<category>{$site->meta_keywords}</category>
<image>
	<url>{if $site->style != "" && $site->style != "/"}{$serverroot}theme/{$site->style}/images/banner.jpg{else}{$serverroot}images/banner.jpg{/if}</url>
	<title>{$site->title|escape}</title>
	<link>{$serverroot}</link>
	<description>Visit {$site->title|escape} - {$site->strapline|escape}</description>
</image>

{foreach from=$releases item=release}
<item>
	<title>{$release.searchname|escape:html}</title>
	<guid isPermaLink="true">{$serverroot}details/{$release.guid}</guid>
	<link>{$serverroot}{if $dl=="1"}getnzb{else}details{/if}/{$release.guid}{if $dl=="1"}.nzb&amp;i={$uid}&amp;r={$rsstoken}{/if}{if $del=="1"}&amp;del=1{/if}</link>
	<comments>{$serverroot}details/{$release.guid}#comments</comments> 	
	<pubDate>{$release.adddate|phpdate_format:"DATE_RSS"}</pubDate> 
	<category>{$release.category_name|escape:html}</category> 	
	<description>{if $api=="1"}{$release.searchname}{else}
<![CDATA[{strip}
	<div>
	{if $release.cover == 1}
		<img style="margin-left:10px;margin-bottom:10px;float:right;" src="{$serverroot}covers/movies/{$release.imdbID}-cover.jpg" width="120" border="0" alt="{$release.searchname|escape:"htmlall"}" />
	{/if}
	{if $release.mu_cover == 1}
		<img style="margin-left:10px;margin-bottom:10px;float:right;" src="{$serverroot}covers/music/{$release.musicinfoID}.jpg" width="120" border="0" alt="{$release.searchname|escape:"htmlall"}" />
	{/if}	
	{if $release.co_cover == 1}
		<img style="margin-left:10px;margin-bottom:10px;float:right;" src="{$serverroot}covers/console/{$release.consoleinfoID}.jpg" width="120" border="0" alt="{$release.searchname|escape:"htmlall"}" />
	{/if}	
	<ul>
	<li>ID: <a href="{$serverroot}details/{$release.guid}">{$release.guid}</a> (Size: {$release.size|fsize_format:"MB"}) </li>
	<li>Name: {$release.searchname}</li>
	<li>Attributes: Category - {$release.category_name}</li>
	<li>Groups: {$release.group_name}</li>
	<li>Poster: {$release.fromname|escape:"htmlall"}</li>
	<li>PostDate: {$release.postdate|phpdate_format:"DATE_RSS"}</li>
	<li>Password: {if $release.passwordstatus == 0}None{elseif $release.passwordstatus == 1}Passworded Rar Archive{elseif $release.passwordstatus == 2}Contains Cab/Ace/RAR Archive{else}Unknown{/if}</li>
	
	{if $release.parentCategoryID == 2000}
		{if $release.imdbID != ""}
		<li>Imdb Info: 
			<ul>
				<li>IMDB Link: <a href="http://www.imdb.com/title/tt{$release.imdbID}/">{$release.searchname}</a></li>
				{if $release.rating != ""}<li>Rating: {$release.rating}</li>{/if}
				{if $release.plot != ""}<li>Plot: {$release.plot}</li>{/if}
				{if $release.year != ""}<li>Year: {$release.year}</li>{/if}
				{if $release.genre != ""}<li>Genre: {$release.genre}</li>{/if}
				{if $release.director != ""}<li>Director: {$release.director}</li>{/if}
				{if $release.actors != ""}<li>Actors: {$release.actors}</li>{/if}
			</ul>
		</li>
		{/if}
	{/if}
	
	{if $release.parentCategoryID == 3000}
		{if $release.musicinfoID > 0}
		<li>Music Info: 
			<ul>
				{if $release.mu_url != ""}<li>Amazon: <a href="{$release.mu_url}">{$release.mu_title}</a></li>{/if}
				{if $release.mu_artist != ""}<li>Artist: {$release.mu_artist}</li>{/if}
				{if $release.mu_genre != ""}<li>Genre: {$release.mu_genre}</li>{/if}
				{if $release.mu_publisher != ""}<li>Publisher: {$release.mu_publisher}</li>{/if}
				{if $release.year != ""}<li>Released: {$release.mu_releasedate|date_format}</li>{/if}
				{if $release.mu_review != ""}<li>Review: {$release.mu_review}</li>{/if}
				{if $release.mu_tracks != ""}
				<li>Track Listing:
					<ol>
						{assign var="tracksplits" value="|"|explode:$release.mu_tracks}
						{foreach from=$tracksplits item=tracksplit}
						<li>{$tracksplit|trim}</li>
						{/foreach}		
					</ol>
				</li>				
				{/if}
			</ul>
		</li>
		{/if}
	{/if}	

	{if $release.parentCategoryID == 1000}
		{if $release.consoleinfoID > 0}
		<li>Console Info: 
			<ul>
				{if $release.co_url != ""}<li>Amazon: <a href="{$release.co_url}">{$release.co_title}</a></li>{/if}
				{if $release.co_genre != ""}<li>Genre: {$release.co_genre}</li>{/if}
				{if $release.co_publisher != ""}<li>Publisher: {$release.co_publisher}</li>{/if}
				{if $release.year != ""}<li>Released: {$release.co_releasedate|date_format}</li>{/if}
				{if $release.co_review != ""}<li>Review: {$release.co_review}</li>{/if}
			</ul>
		</li>
		{/if}
	{/if}	

	</ul>
	
	</div>
	<div style="clear:both;">
	{/strip}]]>
	{/if}
</description>
	{if $dl=="1"}<enclosure url="{$serverroot}getnzb/{$release.guid}.nzb&amp;i={$uid}&amp;r={$rsstoken}{if $del=="1"}&amp;del=1{/if}" length="{$release.size}" type="application/x-nzb" />{/if}


	{foreach from=$release.category_ids|parray:"," item=cat}
<newznab:attr name="category" value="{$cat}" />
	{/foreach}<newznab:attr name="size" value="{$release.size}" />
	<newznab:attr name="files" value="{$release.totalpart}" />
	<newznab:attr name="poster" value="{$release.fromname|escape:html}" />
{if $release.season != ""}	<newznab:attr name="season" value="{$release.season}" />
{/if}
{if $release.episode != ""}	<newznab:attr name="episode" value="{$release.episode}" />
{/if}
{if $release.rageID != "-1" && $release.rageID != "-2"}	<newznab:attr name="rageid" value="{$release.rageID}" />
{/if}
{if $release.tvtitle != ""}	<newznab:attr name="tvtitle" value="{$release.tvtitle|escape:html}" />
{/if}
{if $release.tvairdate != ""}	<newznab:attr name="tvairdate" value="{$release.tvairdate|phpdate_format:"DATE_RSS"}" />
{/if}
{if $release.imdbID != ""}	<newznab:attr name="imdb" value="{$release.imdbID}" />
{/if}
	<newznab:attr name="grabs" value="{$release.grabs}" />
	<newznab:attr name="comments" value="{$release.comments}" />
	<newznab:attr name="password" value="{$release.passwordstatus}" />
	<newznab:attr name="usenetdate" value="{$release.postdate|phpdate_format:"DATE_RSS"}" />	
	<newznab:attr name="group" value="{$release.group_name|escape:html}" />
		
</item>
{/foreach}

</channel>
</rss>