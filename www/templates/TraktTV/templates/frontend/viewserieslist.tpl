<h1>{$page->title}</h1>

<p><b>Jump to</b>:
&nbsp;&nbsp;[ {if $seriesletter == '0-9'}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/series/0-9">0-9</a>{if $seriesletter == '0-9'}</u></b>{/if} 
{foreach $seriesrange as $range}
{if $range == $seriesletter}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/series/{$range}">{$range}</a>{if $range == $seriesletter}</u></b>{/if} 
{/foreach}]
&nbsp;&nbsp;[ <a href="{$smarty.const.WWW_TOP}/myshows" title="List my watched shows">My Shows</a> ]
&nbsp;&nbsp;[ <a href="{$smarty.const.WWW_TOP}/myshows/browse" title="browse your shows">Browse My Shows</a> ]
</p>

<div style="float:right;">
	<form name="ragesearch" action="" method="get">
		<label for="title">Search:</label>
		&nbsp;&nbsp;<input id="title" type="text" name="title" value="{$ragename}" size="25" />
		&nbsp;&nbsp;
		<input type="submit" value="Go" />
	</form>
</div>

{$site->adbrowse}	

{if $serieslist|@count > 0}

<table style="width:100%;" class="data highlight icons" id="browsetable">
	{foreach $serieslist as $sletter => $series}
		<tr>
			<td style="padding-top:15px;" colspan="10"><a href="#top" class="top_link">Top</a><h2>{$sletter}...</h2></td>
		</tr>
		<tr>
			<th width="35%">Name</th>
			<th>Country</th>
			<th width="35%">Genre</th>
			<th class="mid">Option</th>
			<th class="mid">View</th>
		</tr>
		{foreach $series as $s}
			<tr class="{cycle values=",alt"}">
				<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageID}">{$s.releasetitle|escape:"htmlall"}</a>{if $s.prevdate != ''}<br />Last: {$s.previnfo|escape:"htmlall"} aired {$s.prevdate|date_format}{/if}</td>
				<td>{$s.country|escape:"htmlall"}</td>
				<td>{$s.genre|escape:"htmlall"|replace:'|':', '}</td>
				<td class="mid">
					{if $s.userseriesID != ''}
						<a href="{$smarty.const.WWW_TOP}/myshows/edit/{$s.rageID}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="edit" name="series{$s.rageID}" title="Edit">Edit</a>&nbsp;&nbsp;<a href="{$smarty.const.WWW_TOP}/myshows/delete/{$s.rageID}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="remove" name="series{$s.rageID}" title="Remove from My Shows">Remove</a>
					{else}
						<a href="{$smarty.const.WWW_TOP}/myshows/add/{$s.rageID}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="add" name="series{$s.rageID}" title="Add to My Shows">Add</a>
					{/if}
				</td>
				<td class="mid"><a title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageID}">Series</a>&nbsp;&nbsp;{if $s.rageID > 0}<a title="View at TVRage" target="_blank" href="{$site->dereferrer_link}http://www.tvrage.com/shows/id-{$s.rageID}">TVRage</a>&nbsp;&nbsp;<a title="RSS Feed for {$s.releasetitle|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/rss?rage={$s.rageID}&amp;dl=1&amp;i={$userdata.ID}&amp;r={$userdata.rsstoken}">Rss</a>{/if}</td>
			</tr>
		{/foreach}
	{/foreach}
</table>

{else}
<h2>No results</h2>
{/if}
