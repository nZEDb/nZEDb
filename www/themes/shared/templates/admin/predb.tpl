<h1>{$page->title}</h1>
<div style="margin: 0 0 10px 0;">
	<form name="presearch" method="get" action="{$smarty.const.WWW_TOP}/predb.php" id="custom-search-form"
		  class="form-inline form-horizontal col-4 col-lg-4">
		<div id="search" class="input-group col-12 col-lg-12">
			<input type="text" class="form-control" placeholder="Search predb" id="presearch" name="presearch" value="{$lastSearch|escape:'html'}">
			<span class="input-group-btn">
				<button type="submit" value="Go" class="btn btn-default btn-outline">
					<i class="fa fa-search">Search</i>
				</button>
			</span>
		</div>
	</form>
</div>
{$pager}
<table class="data table table-striped responsive-utilities jambo-table">
	<tr>
		<th> Date ({$tz})</th>
		<th> Updated ({$tz})</th>
		<th> Title</th>
		<th> Category</th>
		<th> Source</th>
		<th> Reqid</th>
		<th> Size</th>
		<th> Files</th>
		<th></th>
		<th></th>
	</tr>
	{foreach $results as $result}
		<tr class="{cycle values=",alt"}">
			<td class="predb" style="text-align:center;">
				{$result.created|date_format:"%Y-%m-%d %H:%M:%S"}
			</td>
			<td class="predb" style="text-align: center;">
				<span style="text-align:center;">
					{if isset($result.updated)}
						{$result.updated|date_format:"%Y-%m-%d %H:%M:%S"}
					{else}
						&mdash;
					{/if}
				</span>
			</td>
			<td class="predb" style="text-align:center;">
				{if isset($result.guid)}
					<a style="font-style:italic;text-decoration:underline;color:#{if $result.nuked == 1}009933{elseif $result.nuked > 1}990000{/if};"
					   class="title" title="View details"
					   href="{$smarty.const.WWW_TOP}/../details/{$result.guid}">
						<span title="{if $result.nuked == 1}UNNUKED: {$result.nukereason|escape:"htmlall"}{elseif $result.nuked > 1}NUKED: {$result.nukereason|escape:"htmlall"}{else}{$result.title|escape:"htmlall"}{/if}">{$result.title|escape:"htmlall"}</span>
					</a>
				{else}
					<span style="color:#{if $result.nuked == 1}009933{elseif $result.nuked > 1}990000{/if};"
						  title="{if $result.nuked == 1}UNNUKED: {$result.nukereason|escape:"htmlall"}{elseif $result.nuked > 1}NUKED: {$result.nukereason|escape:"htmlall"}{else}{$result.title|escape:"htmlall"}{/if}">{$result.title|escape:"htmlall"}</span>
				{/if}
			</td>
			<td class="predb" style="text-align:center;">
				{* Console *}
				{* Xbox 360 *}
				{if $result.category == 'XBOX360'}
					<a class="title" title="View category XBOX 360"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::GAME_XBOX360}">Console
						Xbox
						360</a>
				{/if}
				{* Movies *}
				{if in_array($result.category, array('Movies'))}
					<a class="title" title="View category Movies"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::MOVIE_ROOT}">Movies</a>
				{/if}
				{* SD *}
				{if in_array($result.category, array('movies-sd', 'Movies: STD', 'XVid'))}
					<a class="title" title="View category Movies SD"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::MOVIE_SD}">Movies
						SD</a>
				{/if}
				{* HD *}
				{if in_array($result.category, array('Movies: HD', 'X264'))}
					<a class="title" title="View category Movies HD"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::MOVIE_HD}">Movies
						HD</a>
				{/if}
				{* BluRay *}
				{if in_array($result.category, array('BLURAY'))}
					<a class="title" title="View category BluRay"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::MOVIE_BLURAY}">Movies
						BluRay</a>
				{/if}
				{* DVD *}
				{if in_array($result.category, array('DVDR', 'Movies: DVD'))}
					<a class="title" title="View category DVDR"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::MOVIE_DVD}">DVD</a>
				{/if}
				{* Audio *}
				{if in_array($result.category, array('music-audio'))}
					<a class="title" title="View category Music"
					   href="{$smarty.const.WWW_TOP}/../music">Audio</a>
				{/if}
				{* MP3 *}
				{if in_array($result.category, array('MP3', 'Music: MP3'))}
					<a class="title" title="View category MP3"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::MUSIC_MP3}">MP3</a>
				{/if}
				{* Video *}
				{if in_array($result.category, array('MVid', 'Music: MVid'))}
					<a class="title" title="View category Audio Video"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::MUSIC_VIDEO}">Music
						Videos</a>
				{/if}
				{* Audiobook *}
				{if in_array($result.category, array('audiobook', 'Audiobook'))}
					<a class="title" title="View category Audiobook"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::MUSIC_AUDIOBOOK}">Audiobook</a>
				{/if}
				{* Lossless *}
				{if in_array($result.category, array('FLAC', 'Music: FLAC'))}
					<a class="title" title="View category Music Lossless"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::MUSIC_LOSSLESS}">Lossless
						Music</a>
				{/if}
				{* PC *}
				{* 0day *}
				{if in_array($result.category, array('0DAY', 'APPS', 'Apps: PC', 'Apps: Linux', 'DOX'))}
					<a class="title" title="View category PC 0day"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::PC_0DAY}">PC
						0DAY</a>
				{/if}
				{* Mac *}
				{if in_array($result.category, array('Apps: MAC', 'Games: MAC'))}
					<a class="title" title="View category PC Mac"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::PC_MAC}">PC
						Mac</a>
				{/if}
				{* Phone-Other *}
				{if in_array($result.category, array('Apps: Phone', 'PDA'))}
					<a class="title" title="View category Phone Other"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::PC_PHONE_OTHER}">Phone
						Other</a>
				{/if}
				{* Games *}
				{if in_array($result.category, array('GAMES', 'Games: PC', 'Games: Other'))}
					<a class="title" title="View category PC Games"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::PC_GAMES}">PC
						Games</a>
				{/if}
				{* TV *}
				{if in_array($result.category, array('TV'))}
					<a class="title" title="View category TV"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::TV_ROOT}">TV</a>
				{/if}
				{* SD *}
				{if in_array($result.category, array('TV-DVDRIP', 'tv-sd', 'TV: STD', 'TV-XVid'))}
					<a class="title" title="View category TV SD"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::TV_SD}">SDTV</a>
				{/if}
				{* HD *}
				{if in_array($result.category, array('tv-hd', 'TV: HD', 'TV-x264', 'TV-X264'))}
					<a class="title" title="View category TV HD"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::TV_HD}">HDTV</a>
				{/if}
				{* XXX *}
				{if in_array($result.category, array('XXX'))}
					<a class="title" title="View category XXX"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::XXX_ROOT}">XXX</a>
				{/if}
				{* DVD *}
				{if in_array($result.category, array('XXX: DVD'))}
					<a class="title" title="View category XXX DVD"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::XXX_DVD}">XXX
						DVD</a>
				{/if}
				{* XviD *}
				{if in_array($result.category, array('XXX: SD-CLIPS', 'XXX: MOVIES-SD'))}
					<a class="title" title="View category XXX XviD"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::XXX_XVID}">XXX
						SD</a>
				{/if}
				{* x264 *}
				{if in_array($result.category, array('XXX: HD-CLIPS', 'XXX: MOVIES-HD'))}
					<a class="title" title="View category XXX x264"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::XXX_X264}">XXX
						HD</a>
				{/if}
				{* Other *}
				{if in_array($result.category, array('xxx-videos'))}
					<a class="title" title="View category XXX Other"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::XXX_OTHER}">XXX
						Other</a>
				{/if}
				{* Imageset *}
				{if in_array($result.category, array('XXX-IMGSET'))}
					<a class="title" title="View category XXX Imageset"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::XXX_IMAGESET}">XXX
						Imagesets</a>
				{/if}
				{* Books *}
				{if in_array($result.category, array('EBOOK'))}
					<a class="title" title="View category Books"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::BOOKS_ROOT}">Ebooks</a>
				{/if}
				{* Other *}
				{if in_array($result.category, array('Other: E-Books'))}
					<a class="title" title="View category Books Other"
					   href="{$smarty.const.WWW_TOP}/../browse?t={$catClass::BOOKS_UNKNOWN}">Ebooks
						Other</a>
				{/if}
				{if in_array($result.category, array('', 'PRE'))}
					N/A
				{else}
					{$result.category}
				{/if}
			</td>
			<td class="predb" style="text-align:center;">
				{if $result.source == abgx}
					<a title="Visit abgx"
					   href="{$site->dereferrer_link}http://www.abgx.net/rss/x360/posted.rss"
					   target="_blank">
						abgx.net
					</a>
				{/if}
				{if in_array($result.source, array('abErotica', 'abMooVee', 'abTeeVee', 'abForeign'))}
					<a title="Visit allfilled $result.source"
					   href="{$site->dereferrer_link}http://$result.source.allfilled.com/search.php?q={$result.title}&Search=Search"
					   target="_blank">
						$result.source
					</a>
				{/if}
				{if $result.source|strpos:'#a.b.' != false}
					<a title="Visit $result.source on IRC"
					   href="irc://irc.Prison.NET:6667/{str_replace('#a.b.', 'alt.binaries.', $result.source)}"
					   target="_blank">
						$result.source
					</a>
				{/if}
				{if $result.source == omgwtfnzbs}
					<a title="Visit omgwtfnzbs"
					   href="{$site->dereferrer_link}http://rss.omgwtfnzbs.org/rss-info.php"
					   target="_blank">
						omgwtfnzbs
					</a>
				{/if}
				{if $result.source == orlydb}
					<a title="Visit ORLYDB"
					   href="{$site->dereferrer_link}http://orlydb.com/?q={$result.title}"
					   target="_blank">
						ORLYDB
					</a>
				{/if}
				{if $result.source == predbme}
					<a title="Visit PreDB.me"
					   href="{$site->dereferrer_link}http://predb.me/?search={$result.title}"
					   target="_blank">
						PreDB
					</a>
				{/if}
				{if $result.source == prelist}
					<a title="Visit Prelist"
					   href="{$site->dereferrer_link}http://www.prelist.ws/?search={$result.title}"
					   target="_blank">
						Prelist
					</a>
				{/if}
				{if $result.source == "#Pre@zenet"}
					<a title="Visit zenet on IRC" href="irc://irc.zenet.org:6667/Pre"
					   target="_blank">
						Zenet IRC
					</a>
				{/if}
				{if $result.source == "#pre@corrupt"}
					<a title="Visit corrupt on IRC"
					   href="irc://irc.corrupt-net.org:6667/pre"
					   target="_blank">
						Corrupt-Net
					</a>
				{/if}
				{if $result.source == srrdb}
					<a title="Visit srrDB"
					   href="{$site->dereferrer_link}http://www.srrdb.com/browse/{$result.title}"
					   target="_blank">
						srrDB
					</a>
				{/if}
				{if $result.source == "#scnzb"}
					<a title="Visit srrDB" href="irc://irc.Prison.NET:6667/scnzb"
					   target="_blank">
						srrDB
					</a>
				{/if}
				{if $result.source == "#tvnzb"}
					<a title="Visit srrDB" href="irc://irc.Prison.NET:6667/tvnzb"
					   target="_blank">
						srrDB
					</a>
				{/if}
				{if $result.source == "usenet-crawler"}
					<a title="Visit Usenet-Crawler"
					   href="{$site->dereferrer_link}http://www.usenet-crawler.com/predb?q={$result.title}"
					   target="_blank">
						Usenet-Crawler
					</a>
				{/if}
				{if $result.source == womble}
					<a title="Visit Womble"
					   href="{$site->dereferrer_link}http://www.newshost.co.za/?s={$result.title}"
					   target="_blank">
						Womble
					</a>
				{/if}
				{if $result.source == zenet}
					<a title="Visit ZEnet"
					   href="{$site->dereferrer_link}http://pre.zenet.org/?search={$result.title}"
					   target="_blank">
						ZEnet
					</a>
				{else}
					{$result.source}
				{/if}
			</td>
			<td class="predb" style="text-align:center;">
				{if is_numeric($result.requestid) && $result.requestid != 0}
					<a
							class="requestid"
							title="{$result.requestid}"
							href="{$smarty.const.WWW_TOP}/../search?searchadvr=&searchadvsubject={$result.requestid}
															&searchadvposter=&searchadvdaysnew=&searchadvdaysold=&searchadvgroups=-1&searchadvcat=-1
															&searchadvsizefrom=-1&searchadvsizeto=-1&searchadvhasnfo=0&searchadvhascomments=0&search_type=adv"
					>
						{$result.requestid}
					</a>
				{else}
					N/A
				{/if}
			</td>
			<td class="predb" style="text-align:center;">
				{if not in_array($result.size, array('NULL', '', '0MB'))}
					{if strpos($result.size, 'MB') != false && ($result.size|regex_replace:"/(\.\d|,|MB)+/":''|count_characters) > 3}
						{math equation=($result.size|regex_replace:'/(\.\d|,|MB)+/':'' / 1024)|round}GB
					{else}
						{$result.size|regex_replace:"/(\.\d|,)+/":''}
					{/if}
				{else}
					N/A
				{/if}
			</td>
			<td class="predb" style="text-align:center;">
				{if isset($result.files)}
					{$result.files}
				{else}
					N/A
				{/if}
			</td>
			<td class="predb" style="text-align:center;">
				<a
						style="float: right;"
						title="NzbIndex"
						href="{$site->dereferrer_link}http://nzbindex.com/search/?q={$result.title}"
						target="_blank"
				>
					<img src="{$smarty.const.WWW_THEMES}/shared/img/icons/nzbindex.png"/>
					&nbsp;
				</a>
			</td>
			<td class="predb" style="text-align:center;">
				<a
						style="float: right;"
						title="BinSearch"
						href="{$site->dereferrer_link}http://binsearch.info/?q={$result.title}"
						target="_blank"
				>
					<img src="{$smarty.const.WWW_THEMES}/shared/img/icons/binsearch.png"/>
					&nbsp;
				</a>
			</td>
		</tr>
	{/foreach}
</table>
<hr>
<div style="padding-bottom:10px;">
	{$pager}
</div>
