
<h1>Browse Groups</h1>


{$site->adbrowse}

{if $results|@count > 0}

<table style="width:100%;" class="data highlight table table-condensed table-responsive table-striped Sortable" id="browsetable">
	<tr>
                <th>Name</th>
                <th>Description</th>
                <th>Updated</th>
                <th>Releases</th>
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
