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
			<url>{$serverroot}themes/shared/images/logo.png</url>
			<title>{$site->title|escape}</title>
			<link href="{$serverroot}"/>
			<description>Visit {$site->title|escape} - {$site->strapline|escape}</description>
		</image>
		{foreach from=$releases item=release}
			<item>
				<title>{$release.searchname|escape:html}</title>
				<guid isPermaLink="true">{$serverroot}details/{$release.guid}</guid>
				<link href="{$serverroot}{if $dl=="1"}getnzb{else}details{/if}/{$release.guid}{if $dl=="1"}.nzb&amp;i={$uid}&amp;r={$rsstoken}{/if}{if $del=="1"}&amp;del=1{/if}"/>
				<comments>{$serverroot}details/{$release.guid}#comments</comments>
				<pubDate>{$release.adddate|phpdate_format:"DATE_RSS"}</pubDate>
				<category>{$release.category_name|escape:html}</category>
				<description>{if isset($api) && $api=="1"}{$release.searchname}{else}
					<![CDATA[{strip}
					<div>
						{if isset($release_cover) && $release.cover == 1}
							<img style="margin-left:10px;margin-bottom:10px;float:right;"
								 src="{$serverroot}covers/movies/{$release.imdbid}-cover.jpg" width="120" border="0"
								 alt="{$release.searchname|escape:"htmlall"}"/>
						{/if}
						{if isset($release.mu_cover) && $release.mu_cover == 1}
							<img style="margin-left:10px;margin-bottom:10px;float:right;"
								 src="{$serverroot}covers/music/{$release.musicinfoid}.jpg" width="120" border="0"
								 alt="{$release.searchname|escape:"htmlall"}"/>
						{/if}
						{if isset($release.co_cover) && $release.co_cover == 1}
							<img style="margin-left:10px;margin-bottom:10px;float:right;"
								 src="{$serverroot}covers/console/{$release.consoleinfoid}.jpg" width="120" border="0"
								 alt="{$release.searchname|escape:"htmlall"}"/>
						{/if}
						{if isset($release.bo_cover) && $release.bo_cover == 1}
							<img style="margin-left:10px;margin-bottom:10px;float:right;"
								 src="{$serverroot}covers/book/{$release.bookinfoid}.jpg" width="120" border="0"
								 alt="{$release.searchname|escape:"htmlall"}"/>
						{/if}
						<ul>
							<li>ID: <a href="{$serverroot}details/{$release.guid}">{$release.guid}</a></li>
							<li>Name: {$release.searchname}</li>
							<li>Size: {$release.size|fsize_format:"MB"} </li>
							<li>Attributes: Category - <a
										href="{$serverroot}browse?t={$release.categoryid}">{$release.category_name}</a>
							</li>
							<li>Groups: <a href="{$serverroot}browse?g={$release.group_name}">{$release.group_name}</a>
							</li>
							<li>Poster: {$release.fromname|escape:"htmlall"}</li>
							<li>PostDate: {$release.postdate|phpdate_format:"DATE_RSS"}</li>
							<li>
								Password: {if $release.passwordstatus == 0}None{elseif $release.passwordstatus == 1}Possibly Passworded Archive{elseif $release.passwordstatus == 2}Probably not viable{elseif $release.passwordstatus == 10}Passworded Archive{else}Unknown{/if}</li>
							{if isset($release.nfoid) && $release.nfoid != ""}
								<li>Nfo:
									<a href="{$serverroot}api?t=getnfo&amp;id={$release.guid}&amp;raw=1&amp;i={$uid}&amp;r={$rsstoken}">{$release.searchname}
										.nfo</a></li>
							{/if}
							{if isset($release.parentCategoryid) && $release.parentCategoryid == 2000}
								{if $release.imdbid != ""}
									<li>Imdb Info:
										<ul>
											<li>IMDB Link: <a
														href="http://www.imdb.com/title/tt{$release.imdbid}/">{$release.searchname}</a>
											</li>
											{if $release.rating != ""}
												<li>Rating: {$release.rating}</li>{/if}
											{if $release.plot != ""}
												<li>Plot: {$release.plot}</li>{/if}
											{if $release.year != ""}
												<li>Year: {$release.year}</li>{/if}
											{if $release.genre != ""}
												<li>Genre: {$release.genre}</li>{/if}
											{if $release.director != ""}
												<li>Director: {$release.director}</li>{/if}
											{if $release.actors != ""}
												<li>Actors: {$release.actors}</li>{/if}
										</ul>
									</li>
								{/if}
							{/if}
							{if isset($release.parentCategoryid) && $release.parentCategoryid == 3000}
								{if $release.musicinfoid > 0}
									<li>Music Info:
										<ul>
											{if $release.mu_url != ""}
												<li>Amazon: <a href="{$release.mu_url}">{$release.mu_title}</a>
												</li>{/if}
											{if $release.mu_artist != ""}
												<li>Artist: {$release.mu_artist}</li>{/if}
											{if $release.mu_genre != ""}
												<li>Genre: {$release.mu_genre}</li>{/if}
											{if $release.mu_publisher != ""}
												<li>Publisher: {$release.mu_publisher}</li>{/if}
											{if $release.year != ""}
												<li>Released: {$release.mu_releasedate|date_format}</li>{/if}
											{if $release.mu_review != ""}
												<li>Review: {$release.mu_review}</li>{/if}
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
							{if isset($release.parentCategoryid) && $release.parentCategoryid == 1000}
								{if $release.consoleinfoid > 0}
									<li>Console Info:
										<ul>
											{if $release.co_url != ""}
												<li>Amazon: <a href="{$release.co_url}">{$release.co_title}</a>
												</li>{/if}
											{if $release.co_genre != ""}
												<li>Genre: {$release.co_genre}</li>{/if}
											{if $release.co_publisher != ""}
												<li>Publisher: {$release.co_publisher}</li>{/if}
											{if $release.year != ""}
												<li>Released: {$release.co_releasedate|date_format}</li>{/if}
											{if $release.co_review != ""}
												<li>Review: {$release.co_review}</li>{/if}
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
				{if $dl=="1"}
					<enclosure
					url="{$serverroot}getnzb/{$release.guid}.nzb&amp;i={$uid}&amp;r={$rsstoken}{if $del=="1"}&amp;del=1{/if}"
					length="{$release.size}" type="application/x-nzb" />{/if}
				{foreach from=$release.category_ids|parray:"," item=cat}
					<newznab:attr name="category" value="{$cat}"/>
				{/foreach}
				<newznab:attr name="size" value="{$release.size}"/>
				<newznab:attr name="files" value="{$release.totalpart}"/>
				<newznab:attr name="poster" value="{$release.fromname|escape:html}"/>
				{if $release.videos_id > 0}
					<newznab:attr name="videos_id" value="{$release.videos_id}" />
				{/if}
				{if $release.tv_episodes_id > 0}
					<newznab:attr name="episode" value="{$release.tv_episodes_id}" />
				{/if}
				{if $release.imdbid != ""}
					<newznab:attr name="imdb" value="{$release.imdbid}"/>
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
