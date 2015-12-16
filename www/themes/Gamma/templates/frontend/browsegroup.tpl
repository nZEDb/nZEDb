
<h1>Browse Groups</h1>


{$site->adbrowse}

{if $results|@count > 0}

<table style="width:100%;" class="data highlight" id="browsetable">
	<tr>
                <th>name<br/><a title="Sort Descending" href="{$orderbyname_desc}"><img src="{$smarty.const.WWW_TOP}/themes/Gamma/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="{$orderbyname_asc}"><img src="{$smarty.const.WWW_TOP}/themes/Gamma/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
                <th>description<br/><a title="Sort Descending" href="{$orderbydescription_desc}"><img src="{$smarty.const.WWW_TOP}/themes/Gamma/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="{$orderbydescription_asc}"><img src="{$smarty.const.WWW_TOP}/themes/Gamma/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
                <th>updated<br/><a title="Sort Descending" href="{$orderbyupdated_desc}"><img src="{$smarty.const.WWW_TOP}/themes/Gamma/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="{$orderbyupdated_asc}"><img src="{$smarty.const.WWW_TOP}/themes/Gamma/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
                <th>releases<br/><a title="Sort Descending" href="{$orderbyreleases_desc}"><img src="{$smarty.const.WWW_TOP}/themes/Gamma/images/sorting/arrow_down.gif" alt="Sort Descending" /></a><a title="Sort Ascending" href="{$orderbyreleases_asc}"><img src="{$smarty.const.WWW_TOP}/themes/Gamma/images/sorting/arrow_up.gif" alt="Sort Ascending" /></a></th>
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
