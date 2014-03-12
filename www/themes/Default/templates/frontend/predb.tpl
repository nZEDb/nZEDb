<h1>{$page->title}</h1>
{$pager}
<table style="width:100%;margin-bottom:10px; margin-top:5px;" class="data Sortable highlight">
	<tr>
		<th>title</th>
		<th>requestid</th>
		<th>added</th>
		<th>pre-date</th>
		<th>source</th>
		<th>category</th>
		<th>size</th>
	</tr>
	{foreach from=$results item=result}
		<tr class="{cycle values=",alt"}">
			<td class="predb">
				{if isset($result.guid)}
					<a class="title" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.title|escape:"htmlall"}">
						{$result.title|escape:"htmlall"}
					</a>
				{else}
					{$result.title|escape:"htmlall"}
				{/if}
				<a
					style="float: right;"
					title="NzbIndex"
					href="{$site->dereferrer_link}http://nzbindex.com/search/?q={$result.title}"
					target="_blank"
				>
					<img src="{$smarty.const.WWW_TOP}/themes/Default/images/icons/nzbindex.png" />
					&nbsp;
				</a>
				<a
					style="float: right;"
					title="BinSearch"
					href="{$site->dereferrer_link}http://binsearch.info/?q={$result.title}"
					target="_blank"
				>
					<img src="{$smarty.const.WWW_TOP}/themes/Default/images/icons/binsearch.png" />
					&nbsp;
				</a>
			</td>
			<td class="predb">
				{if is_numeric({$result.requestid}) && {$result.requestid} != 0}
					<a
						class="requestid"
						title="{$result.requestid}"
						href="{$smarty.const.WWW_TOP}/search?searchadvr=&searchadvsubject=[{$result.requestid}]
						&searchadvposter=&searchadvdaysnew=&searchadvdaysold=&searchadvgroups=-1&searchadvcat=-1
						&searchadvsizefrom=-1&searchadvsizeto=-1&searchadvhasnfo=0&searchadvhascomments=0&search_type=adv"
					>
						{$result.requestid}
					</a>
				{else}
					N/A
				{/if}
			</td>
			<td class="predb">
				{$result.adddate|date_format:"%Y-%m-%d %H:%M:%S"}
			</td>
			<td class="predb">
				{$result.predate|date_format:"%Y-%m-%d %H:%M:%S"}
			</td>
			<td class="predb">
				{if {$result.source} == abgx}
					<a title="Visit abgx" href="{$site->dereferrer_link}http://www.abgx.net/rss/x360/posted.rss">
						abgx.net
					</a>
				{elseif {$result.source} == omgwtfnzbs}
					<a title="Visit omgwtfnzbs" href="{$site->dereferrer_link}http://rss.omgwtfnzbs.org/rss-info.php">
						omgwtfnzbs.org
					</a>
				{elseif {$result.source} == orlydb}
					<a title="Visit ORLYDB" href="{$site->dereferrer_link}http://orlydb.com/?q={$result.title}" target="_blank">
						ORLYDB.com
					</a>
				{elseif {$result.source} == predbme}
					<a title="Visit PreDB.me" href="{$site->dereferrer_link}http://predb.me/?search={$result.title}" target="_blank">
						PreDB.me
					</a>
				{elseif {$result.source} == prelist}
					<a title="Visit Prelist" href="{$site->dereferrer_link}http://www.prelist.ws/?search={$result.title}" target="_blank">
						Prelist.ws
					</a>
				{elseif {$result.source} == srrdb}
					<a title="Visit srrDB" href="{$site->dereferrer_link}http://www.srrdb.com/browse/{$result.title}" target="_blank">
						srrDB.com
					</a>
				{elseif {$result.source} == "usenet-crawler"}
					<a title="Visit Usenet-Crawler" href="{$site->dereferrer_link}http://www.usenet-crawler.com/predb?q={$result.title}" target="_blank">
						Usenet-Crawler
					</a>
				{elseif {$result.source} == womble}
					<a title="Visit Womble" href="{$site->dereferrer_link}http://www.newshost.co.za/?s={$result.title}" target="_blank">
						Womble's NZB Index
					</a>
				{elseif {$result.source} == zenet}
					<a title="Visit ZEnet" href="{$site->dereferrer_link}http://pre.zenet.org/?search={$result.title}" target="_blank">
						ZEnet.org
					</a>
				{else}
					{$result.source}
				{/if}
			</td>
			<td class="predb">
				{* Console *}
				{* Xbox 360 *}
				{if {$result.category} == 'XBOX360'}
					<a class="title" title="View category XBOX 360" href="{$smarty.const.WWW_TOP}/browse?t=1050">{$result.category}</a>

				{* Movies *}
				{* SD *}
				{elseif in_array({$result.category}, array('movies-sd', 'XVID'))}
					<a class="title" title="View category Movies SD" href="{$smarty.const.WWW_TOP}/browse?t=2030">{$result.category}</a>
				{* HD *}
				{elseif {$result.category} == 'X264'}
					<a class="title" title="View category Movies HD" href="{$smarty.const.WWW_TOP}/browse?t=2040">{$result.category}</a>
				{* BluRay *}
				{elseif in_array({$result.category}, array('BLURAY'))}
					<a class="title" title="View category BluRay" href="{$smarty.const.WWW_TOP}/browse?t=2060">{$result.category}</a>
				{* DVD *}
				{elseif in_array({$result.category}, array('DVDR', 'Movies: DVD'))}
					<a class="title" title="View category DVDR" href="{$smarty.const.WWW_TOP}/browse?t=2070">{$result.category}</a>

				{* Audio *}
				{elseif in_array({$result.category}, array('music-audio'))}
					<a class="title" title="View category Music" href="{$smarty.const.WWW_TOP}/music">{$result.category}</a>
				{* MP3 *}
				{elseif in_array({$result.category}, array('MP3', 'Music: MP3'))}
					<a class="title" title="View category MP3" href="{$smarty.const.WWW_TOP}/browse?t=3010">{$result.category}</a>
				{* Video *}
				{elseif {$result.category} == 'MVID'}
					<a class="title" title="View category Audio Video" href="{$smarty.const.WWW_TOP}/browse?t=3020">{$result.category}</a>
				{* Lossless *}
				{elseif in_array({$result.category}, array('FLAC', 'Music: FLAC'))}
					<a class="title" title="View category Music Lossless" href="{$smarty.const.WWW_TOP}/browse?t=3040">{$result.category}</a>

				{* PC *}
				{* 0day *}
				{elseif {$result.category} == '0DAY'}
					<a class="title" title="View category PC 0day" href="{$smarty.const.WWW_TOP}/browse?t=4010">{$result.category}</a>
				{* Phone-Other *}
				{elseif {$result.category} == 'PDA'}
					<a class="title" title="View category Phone Other" href="{$smarty.const.WWW_TOP}/browse?t=4040">{$result.category}</a>
				{* Games *}
				{elseif in_array({$result.category}, array('GAMES', 'Games: PC'))}
					<a class="title" title="View category PC Games" href="{$smarty.const.WWW_TOP}/browse?t=4050">{$result.category}</a>

				{* TV *}
				{* SD *}
				{elseif in_array({$result.category}, array('tv-sd', 'TV: STD', 'TV-XVID'))}
					<a class="title" title="View category TV SD" href="{$smarty.const.WWW_TOP}/browse?t=5030">{$result.category}</a>
				{* HD *}
				{elseif in_array({$result.category}, array('tv-hd', 'TV: HD', 'TV-x264', 'TV-X264'))}
					<a class="title" title="View category TV HD" href="{$smarty.const.WWW_TOP}/browse?t=5040">{$result.category}</a>

				{* XXX *}
				{elseif {$result.category} == 'XXX'}
					<a class="title" title="View category XXX" href="{$smarty.const.WWW_TOP}/browse?t=6000">{$result.category}</a>
				{* Imageset *}
				{elseif {$result.category} == 'XXX-IMGSET'}
					<a class="title" title="View category XXX Imageset" href="{$smarty.const.WWW_TOP}/browse?t=6060">{$result.category}</a>

				{* Books *}
				{elseif in_array({$result.category}, array('EBOOK'))}
					<a class="title" title="View category Books" href="{$smarty.const.WWW_TOP}/browse?t=8000">{$result.category}</a>

				{elseif {$result.category} == ''}
					N/A
				{else}
					{$result.category}
				{/if}
			</td>
			<td class="predb">
				{if {$result.size} != 'NULL' && {$result.size} != ''}
					{$result.size}
				{else}
					N/A
				{/if}
			</td>
		</tr>
	{/foreach}
</table>
<pager style="padding-bottom:10px;">
	{$pager}
</pager>