
<h1>{$page->title}</h1>

{$pager}

<table style="width:100%;margin-bottom:10px; margin-top:5px;" class="data Sortable highlight">

	<tr>
		<th>title</th>
			{if $isadmin || $ismod}
			<th>requestid</th>
			{/if}
		<th>added</th>
		<th>pre-date</th>
		{if $isadmin || $ismod} <th>source</th> {/if}
		<th>category</th>
		<th>size</th>
	</tr>

	{foreach from=$results item=result}
		<tr class="{cycle values=",alt"}">
			<td class="predb-left" >
				{if isset($result.guid)}
					<a class="title" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.title|escape:"htmlall"}">
						{$result.title|escape:"htmlall"}
					</a>
				{else}
					{$result.title|escape:"htmlall"}
				{/if}
				{if $isadmin || $ismod}
					<a class="rndbtnsml" style="float: right;" title="NzbIndex" href="{$site->dereferrer_link}http://nzbindex.com/search/?q={$result.title}" target="_blank">Nzbindex&nbsp;</a>
					<a class="rndbtnsml" style="float: right;" title="BinSearch" href="{$site->dereferrer_link}http://binsearch.info/?q={$result.title}" target="_blank">Binsearch&nbsp;</a>
				{/if}
			</td>
			{if $isadmin || $ismod}
			<td class="predb">
				{if {$result.requestid} == 0}
				{elseif is_numeric({$result.requestid})}
					{$result.requestid}
				{/if}
			</td>
			{/if}
			<td class="predb">{$result.adddate|date_format:"%Y-%m-%d %H:%M:%S"}</td>
			<td class="predb">{$result.predate|date_format:"%Y-%m-%d %H:%M:%S"}</td>
				{if $isadmin || $ismod}
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
			{/if}
			<td class="predb">
				{if {$result.category} == 'MP3'}
					<a class="title" title="View category MP3" href="{$smarty.const.WWW_TOP}/browse?t=3010">
						{$result.category}
					</a>
				{elseif {$result.category} == 'XXX'}
					<a class="title" title="View category XXX" href="{$smarty.const.WWW_TOP}/browse?t=6000">
						{$result.category}
					</a>
				{elseif {$result.category} == 'DVDR'}
					<a class="title" title="View category DVDR" href="{$smarty.const.WWW_TOP}/browse?t=2070">
						{$result.category}
					</a>
				{elseif {$result.category} == 'TV-X264'}
					<a class="title" title="View category TV HD" href="{$smarty.const.WWW_TOP}/browse?t=5040">
						{$result.category}
					</a>
				{elseif {$result.category} == 'TV-x264'}
					<a class="title" title="View category TV HD" href="{$smarty.const.WWW_TOP}/browse?t=5040">
						{$result.category}
					</a>
				{elseif {$result.category} == 'tv-hd'}
					<a class="title" title="View category TV HD" href="{$smarty.const.WWW_TOP}/browse?t=5040">
						{$result.category}
					</a>
				{elseif {$result.category} == 'XVID'}
					<a class="title" title="View category Movies SD" href="{$smarty.const.WWW_TOP}/browse?t=2030">
						{$result.category}
					</a>
				{elseif {$result.category} == 'movies-sd'}
					<a class="title" title="View category Movies SD" href="{$smarty.const.WWW_TOP}/browse?t=2030">
						{$result.category}
					</a>
				{elseif {$result.category} == 'X264'}
					<a class="title" title="View category Movies HD" href="{$smarty.const.WWW_TOP}/browse?t=2040">
						{$result.category}
					</a>
				{elseif {$result.category} == '0DAY'}
					<a class="title" title="View category PC 0day" href="{$smarty.const.WWW_TOP}/browse?t=4010">
						{$result.category}
					</a>
				{elseif {$result.category} == 'TV-XVID'}
					<a class="title" title="View category TV SD" href="{$smarty.const.WWW_TOP}/browse?t=5030">
						{$result.category}
					</a>
				{elseif {$result.category} == 'tv-sd'}
					<a class="title" title="View category TV SD" href="{$smarty.const.WWW_TOP}/browse?t=5030">
						{$result.category}
					</a>
				{elseif {$result.category} == 'XBOX360'}
					<a class="title" title="View category XBOX 360" href="{$smarty.const.WWW_TOP}/browse?t=1050">
						{$result.category}
					</a>
				{elseif {$result.category} == 'PDA'}
					<a class="title" title="View category Phone Other" href="{$smarty.const.WWW_TOP}/browse?t=4040">
						{$result.category}
					</a>
				{elseif {$result.category} == 'BLURAY'}
					<a class="title" title="View category BluRay" href="{$smarty.const.WWW_TOP}/browse?t=2060">
						{$result.category}
					</a>
				{elseif {$result.category} == 'MVID'}
					<a class="title" title="View category Audio Video" href="{$smarty.const.WWW_TOP}/browse?t=3020">
						{$result.category}
					</a>
				{elseif {$result.category} == 'GAMES'}
					<a class="title" title="View category PC Games" href="{$smarty.const.WWW_TOP}/browse?t=4050">
						{$result.category}
					</a>
				{elseif {$result.category} == 'EBOOK'}
					<a class="title" title="View category Books" href="{$smarty.const.WWW_TOP}/browse?t=8000">
						{$result.category}
					</a>
				{elseif {$result.category} == 'FLAC'}
					<a class="title" title="View category Music Lossless" href="{$smarty.const.WWW_TOP}/browse?t=3040">
						{$result.category}
					</a>
				{else}
					{$result.category}
				{/if}
			</td>
			<td class="predb">
				{if {$result.size} == 0}
				{elseif is_numeric({$result.size})}
					{$result.size}MB
				{else}
					{$result.size}
				{/if}
			</td>
		</tr>
	{/foreach}


</table>

<pager style="padding-bottom:10px;"> {$pager} </pager>
