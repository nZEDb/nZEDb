
<h4>{$page->title}</h4>

<p><b>Jump to</b>:
	&nbsp;&nbsp;<a class="label label-info" href="{$smarty.const.WWW_TOP}/series" title="View available TV series">Series List</a>&nbsp;&nbsp;<a class="label label-info" href="{$smarty.const.WWW_TOP}/myshows/browse">Browse My Shows</a></p>
<p><i class="icon-rss icon-2x" style="color:orange;"></i> Your shows can also be downloaded as an <a href="{$smarty.const.WWW_TOP}/rss?t=-3&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss Feed</a>.</p>

{if $shows|@count > 0}

	<table class="table table-condensed table-highlight table-striped data Sortable" id="browsetable">
		<thead>
		<tr>
			<th width="35%">name</th>
			<th>feed</th>
			<th>category</th>
			<th>added</th>
			<th class="mid">options</th>
		</tr>
		</thead>
		<tbody>
		{foreach from=$shows item=show}
			<tr class="{cycle values=",alt"}">
				<td>
					<a title="View details" href="{$smarty.const.WWW_TOP}/series/{$show.rageid}{if $show.categoryid != ''}?t={$show.categoryid|replace:"|":","}{/if}">{$show.releasetitle|stripslashes|escape:"htmlall"|wordwrap:75:"\n":true}</a>
				</td>
				<td><a href="{$smarty.const.WWW_TOP}/rss?rage={$show.rageid}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}" title="RSS Feed for {$show.releasetitle|stripslashes|escape:"htmlall"} (All Categories)"><i class="icon-rss-sign" style="color:orange;"></i> RSS Feed</td>
				<td class="less">{if $show.categoryNames != ''}{$show.categoryNames|escape:"htmlall"}{else}All{/if}</td>
				<td class="less" title="Added on {$show.createddate}">{$show.createddate|date_format}</td>
				<td class="mid"><a href="{$smarty.const.WWW_TOP}/myshows/edit/{$show.rageid}" class="myshows label label-warning" rel="edit" name="series{$show.rageid}" title="Edit Categories">Edit</a>&nbsp;&nbsp;<a href="{$smarty.const.WWW_TOP}/myshows/delete/{$show.rageid}" class="myshows label label-danger" rel="remove" name="series{$show.rageid}" title="Remove from My Shows">Remove</a></td>
			</tr>
		{/foreach}
		</tbody>
	</table>

{else}
	<h4>No shows bookmarked</h4>
{/if}