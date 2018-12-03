{include file='elements/ads.tpl' ad=$site->adbrowse}
{if $results|@count > 0}
	{$pager}
	<table class="table-striped table-condensed table-highlight data Sortable table" id="browsetable">
		<thead>
		<tr>
			<th>name</th>
			<th>description</th>
			<th>updated</th>
		</tr>
		</thead>
		<tbody>
		{foreach $results as $result}
			{if $pagertotalitems > 0}
				<tr>
					<td>
						<a title="Browse releases from {$result.name|replace:"alt.binaries":"a.b"}" href="{$smarty.const.WWW_TOP}/browse?g={$result.name}">{$result.name|replace:"alt.binaries":"a.b"}</a>
					</td>
					<td>
						{$result.description}
					</td>
					<td class="less">{$result.last_updated|timeago} ago</td>
				</tr>
			{/if}
		{/foreach}
		</tbody>
	</table>
	{$pager}
{/if}
