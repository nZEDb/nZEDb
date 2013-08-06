<p>
<a href="{$smarty.const.WWW_TOP}/upcoming/1">Box Office</a> |
<a href="{$smarty.const.WWW_TOP}/upcoming/2">In Theatre</a> |
<a href="{$smarty.const.WWW_TOP}/upcoming/3">Opening</a> |
<a href="{$smarty.const.WWW_TOP}/upcoming/4">Upcoming</a> |
<a href="{$smarty.const.WWW_TOP}/upcoming/5">DVD Releases</a>
</p>

{$site->adbrowse}

{if $data|@count > 0}

<table class="table data highlight icons" id="coverstable">
		<thead>
        <tr>
			<th></th>
			<th>Name</th>
		</tr>
</thead>
<tbody>
		{foreach $data as $result}
		<tr class="{cycle values=",alt"}">
			<td class="mid">
				<div class="movcover">
					<img class="shadow img-polaroid" src="{$result->posters->profile}" width="120" border="0" alt="{$result->title|escape:"htmlall"}" />
					<div class="movextra">
					</div>
				</div>
			</td>
			<td colspan="3" class="left">
				<h2><a href="{$smarty.const.WWW_TOP}/movies?title={$result->title}&year={$result->year}">{$result->title|escape:"htmlall"}</a> (<a class="title" title="{$result->year}" href="{$smarty.const.WWW_TOP}/movies?year={$result->year}">{$result->year}</a>) {if $result->ratings->critics_score > 0}{$result->ratings->critics_score}/100{/if}</h2>
				{if $result->synopsis == ""}No synopsis. Check <a target="_blank" href="{$site->dereferrer_link}{$result->links->alternate}" title="View Rotten Tomatoes Details">Rotten Tomatoes</a> for more information.{else}{$result->synopsis}{/if}
				{if $result->abridged_cast|@count > 0}
					<br /><br/>
					<b>Starring:</b>
					{foreach from=$result->abridged_cast item=cast name=cast}
						<a href="{$smarty.const.WWW_TOP}/movies?actors={$cast->name|escape:"htmlall"}" title="Search for movies starring {$cast->name|escape:"htmlall"}">{$cast->name|escape:"htmlall"}</a>
						{if $smarty.foreach.cast.last}<br/>{else},{/if}
					{/foreach}
				{else}
					<br/>
				{/if}
				<a class="label label-success" target="_blank" href="{$site->dereferrer_link}{$result->links->alternate}" title="View Rotten Tomatoes Details">Rotten Tomatoes</a>
			</td>
		</tr>
		{/foreach}
</tbody>
</table>

{else}
<h2>No results</h2>
{/if}
