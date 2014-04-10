{if $site->adbrowse}
	{$site->adbrowse}
{/if}
<h1>Browse Groups</h1>
{if $results|@count > 0}
<table style="width:100%;" class="data highlight Sortable" id="browsetable">
	<tr>
		<th>name</th>
		<th>description</th>
		<th>updated</th>
		<th>releases</th>
	</tr>

	{foreach from=$results item=result}
		{if $result.num_releases > 0}
		<tr class="{cycle values=",alt"}">
			<td>
				<a title="Browse releases from {$result.name|replace:"alt.binaries":"a.b"}" href="{$smarty.const.WWW_TOP}/browse?g={$result.name}">{$result.name|replace:"alt.binaries":"a.b"}</a>
			</td>
			<td>
					{$result.description}
			</td>
			<td class="less">{$result.last_updated|timeago} ago</td>
			<td class="less">{$result.num_releases}</td>
		</tr>
		{/if}
	{/foreach}

</table>

{/if}