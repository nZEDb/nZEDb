
<h1>My Cart</h1>

<p>
Your cart can be downloaded as an <a href="{$smarty.const.WWW_TOP}/rss?t=-2&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}&amp;del=1">Rss Feed</a>.
</p>

{if $results|@count > 0}

<form id="nzb_multi_operations_form" action="get">

<div class="nzb_multi_operations">
	<small>With Selected:</small>
	<input type="button" class="nzb_multi_operations_cartdelete" value="Delete" />
</div>

<table style="width:100%;" class="data highlight" id="browsetable">
	<tr>
		<th width="50"><input id="chkSelectAll" type="checkbox" class="nzb_check_all" /><label for="chkSelectAll" style="display:none;">Select All</label></th>
		<th>name</th>
		<th>added</th>
		<th>options</th>
	</tr>

	{foreach from=$results item=result}
		<tr class="{cycle values=",alt"}">
			<td class="check"><input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" value="{$result.id}" /></td>
			<td>
				<a title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.searchname|escape:"htmlall"|wordwrap:75:"\n":true}</a>
			</td>
			<td class="less" title="Added on {$result.createddate}">{$result.createddate|date_format}</td>
			<td><a title="Delete from your cart" href="?delete={$result.id}">delete</a></td>
		</tr>
	{/foreach}

</table>
</form>

{else}
<h2>No NZBs in cart</h2>
{/if}
