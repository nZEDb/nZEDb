{if $site->adbrowse}
	{$site->adbrowse}
{/if}
<h1>Browse Groups</h1>
{if $results|@count > 0}
	{$pager}
	<table style="width:100%;" class="data highlight Sortable" id="browsetable">
		<thead>
		<tr>
			<th>name</th>
			<th>description</th>
			<th>updated</th>
		</tr>
		</thead>

		<tbody>
		{if $pagertotalitems > 0}
			{foreach $results as $result}
			<tr class="{cycle values=",alt"}">
				<td>
					<a title="Browse releases from {$result.name|replace:"alt.binaries":"a.b"}" href="{$smarty.const.WWW_TOP}/browse?g={$result.name}">{$result.name|replace:"alt.binaries":"a.b"}</a>
				</td>
				<td>
						{$result.description}
				</td>
				<td class="less">{$result.last_updated|timeago} ago</td>
			</tr>
			{/foreach}
		{/if}
		</tbody>

	</table>
	{$pager}
{/if}
