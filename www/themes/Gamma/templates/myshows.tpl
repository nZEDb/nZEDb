<h2>{$page->title}</h2>

<center>
<div class="btn-group">
	<a class="btn btn-small" title="View available TV series" href="{$smarty.const.WWW_TOP}/series" >Series List</a>
	<a class="btn btn-small" title="View a list of all releases in your shows" href="{$smarty.const.WWW_TOP}/myshows/browse">Browse My Shows</a>
	<a class="btn btn-small" title="All releases in your shows as an RSS feed" href="{$smarty.const.WWW_TOP}/rss?t=-3&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss <i class="fa fa-rss"></i></a>
</div>
</center>
<br>

{if $shows|@count > 0}

<table class="data highlight Sortable table table-striped" id="browsetable">
	<tr>
		<th>name</th>
		<th width="80">category</th>
		<th width="80">added</th>
		<th width="80" class="mid">options</th>
	</tr>

	{foreach from=$shows item=show}
		<tr class="{cycle values=",alt"}">
			<td>
				<a title="View details" href="{$smarty.const.WWW_TOP}/series/{$show.videos_id}{if $show.categoryid != ''}?t={$show.categoryid|replace:"|":","}{/if}"><h5>{$show.title|escape:"htmlall"|wordwrap:75:"\n":true}</h5></a>
			</td>
			<td class="less">{if $show.categoryNames != ''}{$show.categoryNames|escape:"htmlall"}{else}All{/if}</td>
			<td class="less" style="width:100px;" title="Added on {$show.createddate}">{$show.createddate|date_format}</td>
			<td class="mid" style="width:100px;">
				<div class="btn-group">
					<a class="btn btn-mini btn-warning" href="{$smarty.const.WWW_TOP}/myshows/edit/{$show.videos_id}" class="myshows" rel="edit" name="series{$show.videos_id}" title="Edit Categories">Edit</a>&nbsp;&nbsp;
					<a class="btn btn-mini btn-danger" href="{$smarty.const.WWW_TOP}/myshows/delete/{$show.videos_id}" class="myshows" rel="remove" name="series{$show.videos_id}" title="Remove from My Shows">Remove</a>
				</div>
				</td>
		</tr>
	{/foreach}

</table>

{else}
<div class="alert">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<strong>Sorry!</strong> No shows bookmarked
</div>
{/if}
