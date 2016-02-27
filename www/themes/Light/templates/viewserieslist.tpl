{if $site->adbrowse}
	{$site->adbrowse}
{/if}
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
	<form name="showsearch" action="" method="get">
		<label for="title">Search:</label>
		&nbsp;&nbsp;<input id="title" type="text" name="title" value="{$showname}" size="25" />
		&nbsp;&nbsp;
		<input class="rndbtn" type="submit" value="Go" />
	</form>
</div>
{if $serieslist|@count > 0}
	<table style="width:100%;" class="data highlight icons" id="browsetable">
		{foreach $serieslist as $sletter => $series}
			<tr>
				<th width="35%">Name</th>
				<th>Country</th>
				<th width="35%">Genre</th>
				<th class="mid">Option</th>
				<th class="mid">View</th>
			</tr>
			{foreach $series as $s}
				<tr class="{cycle values=",alt"}">
					<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.videos_id}">{$s.title|escape:"htmlall"}</a>Last: {$s.previnfo|escape:"htmlall"} aired {$s.prevdate|date_format}{/if}</td>
					<td>{$s.country|escape:"htmlall"}</td>
					<td class="mid">
						{if $s.userseriesid != ''}
							<a href="{$smarty.const.WWW_TOP}/myshows/edit/{$s.videos_id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="edit" name="series{$s.videos_id}" title="Edit">Edit</a>&nbsp;&nbsp;<a href="{$smarty.const.WWW_TOP}/myshows/delete/{$s.videos_id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="remove" name="series{$s.videos_id}" title="Remove from My Shows">Remove</a>
						{else}
							<a href="{$smarty.const.WWW_TOP}/myshows/add/{$s.videos_id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="add" name="series{$s.videos_id}" title="Add to My Shows">Add</a>
						{/if}
					</td>
					<td class="mid"><a title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.videos_id}">Series</a>&nbsp;&nbsp;{if $s.videos_id > 0}<a title="View at TVRage" target="_blank" href="{$site->dereferrer_link}http://www.tvrage.com/shows/id-{$s.videos_id}">TVRage</a>&nbsp;&nbsp;<a title="RSS Feed for {$s.title|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/rss?show={$s.videos_id}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss</a>{/if}</td>
				</tr>
			{/foreach}
		{/foreach}
	</table>
{else}
	<h2>No results</h2>
{/if}